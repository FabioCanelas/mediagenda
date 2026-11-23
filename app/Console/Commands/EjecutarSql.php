<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

/**
 * Ejecuta un script SQL completo en PostgreSQL y muestra el resultado de su última
 * consulta. Equivale a pegar el script en el SQL Editor de Supabase; sirve para los
 * scripts de database/sql/postgres sin instalar psql.
 */
#[Signature('base:sql {archivo : Ruta del script .sql} {--conexion=pgsql_migrador : Conexión con la que se ejecuta}')]
#[Description('Ejecuta un script SQL en PostgreSQL y muestra el resultado de su última consulta')]
class EjecutarSql extends Command
{
    public function handle(): int
    {
        $archivo = (string) $this->argument('archivo');

        if (! is_file($archivo)) {
            $this->error("No existe el archivo {$archivo}.");

            return self::FAILURE;
        }

        $db = DB::connection($this->option('conexion'));

        if ($db->getDriverName() !== 'pgsql') {
            $this->error('Este comando es para PostgreSQL. En MariaDB usa database/sql/ejecutar.sh.');

            return self::FAILURE;
        }

        $pdo = $db->getPdo();
        // Sin preparar la sentencia, PostgreSQL acepta varias instrucciones en un solo envío.
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        try {
            $resultado = $pdo->query((string) file_get_contents($archivo));
            $filas = $resultado === false ? [] : $resultado->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $error) {
            $this->error(strtok($error->getMessage(), "\n") ?: 'El script falló.');

            return self::FAILURE;
        }

        if ($filas !== []) {
            $this->table(array_keys($filas[0]), array_map(
                fn (array $fila): array => array_map(fn (mixed $valor): string => match (true) {
                    $valor === true => 'sí',
                    $valor === false => 'no',
                    default => (string) $valor,
                }, $fila),
                $filas,
            ));
        }

        $this->info("Ejecutado: {$archivo}");

        return self::SUCCESS;
    }
}
