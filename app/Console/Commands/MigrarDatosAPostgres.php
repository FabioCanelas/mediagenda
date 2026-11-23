<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Migración heterogénea MariaDB → PostgreSQL (Supabase).
 *
 * La estructura ya la crearon las migraciones en PostgreSQL (con sus triggers,
 * CHECK y vistas); este comando copia solo los DATOS:
 *  1. Ordena las tablas según sus llaves foráneas (primero las referenciadas).
 *  2. Apaga los triggers propios mientras copia, para no duplicar la auditoría ni
 *     bloquear datos históricos legítimos (por ejemplo, recetas ya emitidas).
 *     Las llaves foráneas y los CHECK siguen activos y validan cada fila.
 *  3. Convierte los tipos que cambian (TINYINT 0/1 → BOOLEAN) y omite las columnas
 *     calculadas por la base (el puntaje del ACT se recalcula solo).
 *  4. Ajusta los contadores de las llaves autoincrementales.
 *  5. Compara cuántas filas hay en cada tabla en el origen y en el destino.
 * Todo ocurre en una transacción: si algo falla, el destino queda como estaba.
 */
#[Signature('base:migrar-datos
    {--desde=mysql_migrador : Conexión de origen (MariaDB)}
    {--hacia=pgsql_migrador : Conexión de destino (PostgreSQL), con el usuario dueño de las tablas}
    {--vaciar : Vacía las tablas de destino antes de copiar}
    {--archivos : Copia también los estudios y los PDF al disco de archivos configurado}')]
#[Description('Copia los datos de MariaDB a PostgreSQL (Supabase) y verifica que no falte nada')]
class MigrarDatosAPostgres extends Command
{
    /**
     * Tablas técnicas que no se copian: sesiones, caché, colas y el registro de migraciones.
     *
     * @var array<int, string>
     */
    private const OMITIR = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

    public function handle(): int
    {
        $origen = DB::connection($this->option('desde'));
        $destino = DB::connection($this->option('hacia'));

        if (! in_array($origen->getDriverName(), ['mysql', 'mariadb'], true) || $destino->getDriverName() !== 'pgsql') {
            $this->error('El origen debe ser MariaDB y el destino PostgreSQL.');

            return self::FAILURE;
        }

        $tablas = $this->tablasEnOrden($destino);
        $faltantes = array_values(array_diff($tablas, $origen->getSchemaBuilder()->getTableListing(schemaQualified: false)));

        if ($faltantes !== []) {
            $this->error('Estas tablas no existen en el origen: '.implode(', ', $faltantes));

            return self::FAILURE;
        }

        $ocupadas = array_values(array_filter($tablas, fn (string $tabla): bool => $destino->table($tabla)->exists()));

        if ($ocupadas !== [] && ! $this->option('vaciar')) {
            $this->error('El destino ya tiene datos en: '.implode(', ', $ocupadas).'. Usa --vaciar para reemplazarlos.');

            return self::FAILURE;
        }

        $this->info('Copiando '.count($tablas).' tablas de '.$this->option('desde').' a '.$this->option('hacia').'…');

        $destino->transaction(function () use ($origen, $destino, $tablas): void {
            $lista = implode(', ', array_map(fn (string $tabla): string => "\"{$tabla}\"", $tablas));

            if ($this->option('vaciar')) {
                $destino->unprepared("TRUNCATE {$lista} RESTART IDENTITY CASCADE");
            }

            foreach ($tablas as $tabla) {
                $destino->unprepared("ALTER TABLE \"{$tabla}\" DISABLE TRIGGER USER");
            }

            foreach ($tablas as $tabla) {
                $copiadas = $this->copiarTabla($origen, $destino, $tabla);
                $this->line(sprintf('  %-30s %6d filas', $tabla, $copiadas));
            }

            foreach ($tablas as $tabla) {
                $destino->unprepared("ALTER TABLE \"{$tabla}\" ENABLE TRIGGER USER");
                $this->ajustarContador($destino, $tabla);
            }
        });

        $diferencias = $this->verificar($origen, $destino, $tablas);

        if ($this->option('archivos')) {
            $diferencias += $this->copiarArchivos($destino);
        }

        if ($diferencias > 0) {
            $this->error("La verificación encontró {$diferencias} diferencia(s).");

            return self::FAILURE;
        }

        $this->info('Migración completa y verificada.');

        return self::SUCCESS;
    }

    /**
     * Tablas del esquema de destino ordenadas para que cada una se copie después de
     * las tablas a las que apuntan sus llaves foráneas (orden topológico).
     *
     * @return array<int, string>
     */
    private function tablasEnOrden(Connection $destino): array
    {
        $esquema = $destino->getSchemaBuilder();
        $tablas = array_values(array_diff($esquema->getTableListing(schemaQualified: false), self::OMITIR));
        $dependencias = [];

        foreach ($tablas as $tabla) {
            $dependencias[$tabla] = collect($esquema->getForeignKeys($tabla))
                ->pluck('foreign_table')
                ->reject(fn (string $referida): bool => $referida === $tabla || ! in_array($referida, $tablas, true))
                ->unique()
                ->values()
                ->all();
        }

        $orden = [];

        while (count($orden) < count($tablas)) {
            $listas = array_filter($tablas, fn (string $tabla): bool => ! in_array($tabla, $orden, true)
                && array_diff($dependencias[$tabla], $orden) === []);

            if ($listas === []) {
                throw new RuntimeException('Las llaves foráneas forman un ciclo; no se puede ordenar la copia.');
            }

            sort($listas);
            array_push($orden, ...$listas);
        }

        return $orden;
    }

    private function copiarTabla(Connection $origen, Connection $destino, string $tabla): int
    {
        $columnasDestino = collect($destino->getSchemaBuilder()->getColumns($tabla))
            ->filter(fn (array $columna): bool => empty($columna['generation']));

        $nombres = $columnasDestino->pluck('name')
            ->intersect($origen->getSchemaBuilder()->getColumnListing($tabla))
            ->values()
            ->all();

        $booleanas = $columnasDestino->where('type_name', 'bool')->pluck('name')->all();
        $copiadas = 0;

        $insertar = function (Collection $filas) use ($destino, $tabla, $booleanas, &$copiadas): void {
            $destino->table($tabla)->insert($filas->map(function (object $fila) use ($booleanas): array {
                $fila = (array) $fila;

                foreach ($booleanas as $columna) {
                    $fila[$columna] = $fila[$columna] === null ? null : (bool) $fila[$columna];
                }

                return $fila;
            })->all());

            $copiadas += $filas->count();
        };

        $consulta = $origen->table($tabla)->select($nombres);

        if (in_array('id', $nombres, true)) {
            $consulta->chunkById(500, $insertar, 'id');
        } elseif (($filas = $consulta->get())->isNotEmpty()) {
            $insertar($filas);
        }

        return $copiadas;
    }

    /**
     * Después de insertar con los mismos id, el siguiente id nuevo debe continuar la serie.
     */
    private function ajustarContador(Connection $destino, string $tabla): void
    {
        $id = collect($destino->getSchemaBuilder()->getColumns($tabla))->firstWhere('name', 'id');

        if ($id === null || ! in_array($id['type_name'], ['int2', 'int4', 'int8'], true)) {
            return;
        }

        $destino->select(
            "SELECT setval(pg_get_serial_sequence(?, 'id'), COALESCE((SELECT MAX(id) FROM \"{$tabla}\"), 0) + 1, false)",
            ["\"{$tabla}\""],
        );
    }

    /**
     * @param  array<int, string>  $tablas
     */
    private function verificar(Connection $origen, Connection $destino, array $tablas): int
    {
        $filas = [];
        $diferencias = 0;

        foreach ($tablas as $tabla) {
            $enOrigen = $origen->table($tabla)->count();
            $enDestino = $destino->table($tabla)->count();
            $coincide = $enOrigen === $enDestino;
            $diferencias += $coincide ? 0 : 1;
            $filas[] = [$tabla, $enOrigen, $enDestino, $coincide ? '✓' : '✗'];
        }

        $this->newLine();
        $this->table(['Tabla', 'MariaDB', 'PostgreSQL', ''], $filas);

        return $diferencias;
    }

    /**
     * Sube al disco de archivos (Supabase Storage) los estudios y PDF guardados en el
     * disco local, y comprueba que la huella SHA-256 de lo subido coincide con la base.
     */
    private function copiarArchivos(Connection $destino): int
    {
        $discoDestino = (string) config('mediagenda.archivos.disco');

        if ($discoDestino === 'local') {
            $this->warn('El disco de archivos es "local"; no hay nada que subir (ARCHIVOS_DISCO).');

            return 0;
        }

        $local = Storage::disk('local');
        $remoto = Storage::disk($discoDestino);
        $problemas = 0;

        $archivos = $destino->table('adjuntos')->select(['archivo', 'sha256'])
            ->union($destino->table('documentos_emitidos')->select(['archivo', 'sha256']))
            ->get();

        $this->newLine();
        $this->info("Subiendo {$archivos->count()} archivos al disco {$discoDestino}…");

        foreach ($archivos as $archivo) {
            if (! $local->exists($archivo->archivo)) {
                $this->warn("  Falta en el disco local: {$archivo->archivo}");
                $problemas++;

                continue;
            }

            if (! $remoto->exists($archivo->archivo)) {
                $remoto->put($archivo->archivo, $local->get($archivo->archivo));
            }

            if (hash('sha256', (string) $remoto->get($archivo->archivo)) !== $archivo->sha256) {
                $this->warn("  La huella no coincide: {$archivo->archivo}");
                $problemas++;
            }
        }

        $this->line('  '.($archivos->count() - $problemas).' archivos verificados.');

        return $problemas;
    }
}
