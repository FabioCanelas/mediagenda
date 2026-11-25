<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Verificación de la migración, solo de lectura: compara el origen (MariaDB) con
 * el destino (PostgreSQL en Supabase) en dos niveles.
 *
 *  1. Inventario de objetos y tamaño: tablas, columnas, vistas, llaves foráneas,
 *     CHECK, triggers, índices, funciones y políticas de seguridad por filas.
 *  2. Filas de cada una de las tablas del consultorio.
 *
 * No escribe nada en ninguna de las dos bases. Las diferencias de filas posteriores
 * a la migración (por ejemplo, usuarios creados o auditoría registrada en la web)
 * se muestran marcadas para explicarlas, no se corrigen.
 */
#[Signature('base:comparar
    {--desde=mysql_migrador : Conexión de origen (MariaDB)}
    {--hacia=pgsql_migrador : Conexión de destino (PostgreSQL)}')]
#[Description('Compara origen y destino de la migración: objetos, tamaño y filas por tabla (solo lectura)')]
class CompararOrigenDestino extends Command
{
    /**
     * Tablas técnicas de Laravel: no son datos del consultorio.
     *
     * @var array<int, string>
     */
    private const TECNICAS = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

    public function handle(): int
    {
        $origen = DB::connection($this->option('desde'));
        $destino = DB::connection($this->option('hacia'));

        if (! in_array($origen->getDriverName(), ['mysql', 'mariadb'], true) || $destino->getDriverName() !== 'pgsql') {
            $this->error('El origen debe ser MariaDB y el destino PostgreSQL.');

            return self::FAILURE;
        }

        $base = $origen->getDatabaseName();
        $esquema = (string) $destino->getSchemaBuilder()->getCurrentSchemaName();

        $this->info("Origen: MariaDB {$this->versionDe($origen)} · base {$base}");
        $this->info("Destino: PostgreSQL {$this->versionDe($destino)} · esquema {$esquema}");

        $this->newLine();
        $this->line('<options=bold>1. Inventario de objetos</>');
        $this->table(['Objeto', 'MariaDB', 'PostgreSQL', 'Nota'], $this->inventario($origen, $base, $destino, $esquema));

        $this->newLine();
        $this->line('<options=bold>2. Filas por tabla del consultorio</>');
        [$filas, $iguales] = $this->filasPorTabla($origen, $destino, $esquema);
        $this->table(['Tabla', 'MariaDB', 'PostgreSQL', ''], $filas);
        $this->line("  {$iguales} de ".count($filas).' tablas con la misma cantidad de filas.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    private function inventario(Connection $origen, string $base, Connection $destino, string $esquema): array
    {
        $enOrigen = fn (string $sql): int => (int) $origen->selectOne($sql, [$base])->n;
        $enDestino = fn (string $sql): int => (int) $destino->selectOne($sql, [$esquema])->n;

        $tamanoOrigen = (float) $origen->selectOne('SELECT COALESCE(SUM(data_length + index_length), 0) AS b FROM information_schema.TABLES WHERE table_schema = ?', [$base])->b;
        $tamanoDestino = (float) $destino->selectOne("SELECT COALESCE(SUM(pg_total_relation_size(c.oid)), 0) AS b FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = ? AND c.relkind = 'r'", [$esquema])->b;

        return [
            ['Tablas', (string) $enOrigen("SELECT COUNT(*) AS n FROM information_schema.TABLES WHERE table_schema = ? AND table_type = 'BASE TABLE'"),
                (string) $enDestino('SELECT COUNT(*) AS n FROM pg_tables WHERE schemaname = ?'), '27 del consultorio + 8 técnicas'],
            ['Columnas', (string) $enOrigen("SELECT COUNT(*) AS n FROM information_schema.COLUMNS c JOIN information_schema.TABLES t ON t.table_schema = c.table_schema AND t.table_name = c.table_name WHERE c.table_schema = ? AND t.table_type = 'BASE TABLE'"),
                (string) $enDestino("SELECT COUNT(*) AS n FROM information_schema.columns c JOIN information_schema.tables t ON t.table_schema = c.table_schema AND t.table_name = c.table_name WHERE c.table_schema = ? AND t.table_type = 'BASE TABLE'"), ''],
            ['Vistas', (string) $enOrigen('SELECT COUNT(*) AS n FROM information_schema.VIEWS WHERE table_schema = ?'),
                (string) $enDestino('SELECT COUNT(*) AS n FROM pg_views WHERE schemaname = ?'), ''],
            ['Llaves foráneas', (string) $enOrigen('SELECT COUNT(*) AS n FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE constraint_schema = ?'),
                (string) $enDestino("SELECT COUNT(*) AS n FROM pg_constraint k JOIN pg_namespace n ON n.oid = k.connamespace WHERE n.nspname = ? AND k.contype = 'f'"), 'todas ON DELETE RESTRICT'],
            ['Restricciones CHECK', (string) $enOrigen('SELECT COUNT(*) AS n FROM information_schema.CHECK_CONSTRAINTS WHERE constraint_schema = ?'),
                (string) $enDestino("SELECT COUNT(*) AS n FROM pg_constraint k JOIN pg_namespace n ON n.oid = k.connamespace WHERE n.nspname = ? AND k.contype = 'c'"), 'MariaDB suma un json_valid() por cada columna JSON; PostgreSQL usa el tipo json'],
            ['Triggers', (string) $enOrigen('SELECT COUNT(*) AS n FROM information_schema.TRIGGERS WHERE trigger_schema = ?'),
                (string) $enDestino('SELECT COUNT(*) AS n FROM pg_trigger g JOIN pg_class c ON c.oid = g.tgrelid JOIN pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = ? AND NOT g.tgisinternal'), '75 de auditoría + 18 de reglas'],
            ['Índices (con llaves primarias)', (string) $enOrigen('SELECT COUNT(DISTINCT table_name, index_name) AS n FROM information_schema.STATISTICS WHERE table_schema = ?'),
                (string) $enDestino('SELECT COUNT(*) AS n FROM pg_indexes WHERE schemaname = ?'), 'MariaDB crea solo un índice por cada llave foránea; PostgreSQL no'],
            ['Funciones', (string) $enOrigen('SELECT COUNT(*) AS n FROM information_schema.ROUTINES WHERE routine_schema = ?'),
                (string) $enDestino('SELECT COUNT(*) AS n FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace WHERE n.nspname = ?'), 'en PostgreSQL cada trigger llama a una función PL/pgSQL'],
            ['Políticas de seguridad por filas', 'no existe',
                (string) $enDestino('SELECT COUNT(*) AS n FROM pg_policies WHERE schemaname = ?'), 'una por tabla, solo para mediagenda_app'],
            ['Tamaño (datos + índices)', $this->megabytes($tamanoOrigen), $this->megabytes($tamanoDestino), 'cada motor guarda y reserva espacio a su manera'],
        ];
    }

    /**
     * @return array{0: array<int, array{0: string, 1: int, 2: int, 3: string}>, 1: int}
     */
    private function filasPorTabla(Connection $origen, Connection $destino, string $esquema): array
    {
        $tablas = array_values(array_diff($destino->getSchemaBuilder()->getTableListing($esquema, schemaQualified: false), self::TECNICAS));
        sort($tablas);
        $filas = [];
        $iguales = 0;

        foreach ($tablas as $tabla) {
            $enOrigen = $origen->table($tabla)->count();
            $enDestino = $destino->table($tabla)->count();
            $iguales += $enOrigen === $enDestino ? 1 : 0;
            $marca = match (true) {
                $enOrigen === $enDestino => '✓',
                $enDestino > $enOrigen => '+'.($enDestino - $enOrigen).' registrados en la web después de migrar',
                default => '✗ faltan '.($enOrigen - $enDestino),
            };
            $filas[] = [$tabla, $enOrigen, $enDestino, $marca];
        }

        return [$filas, $iguales];
    }

    private function versionDe(Connection $conexion): string
    {
        $version = (string) $conexion->selectOne($conexion->getDriverName() === 'pgsql' ? 'SHOW server_version' : 'SELECT VERSION() AS server_version')->server_version;

        return strtok($version, ' -');
    }

    private function megabytes(float $bytes): string
    {
        return number_format($bytes / 1048576, 2, ',', '.').' MB';
    }
}
