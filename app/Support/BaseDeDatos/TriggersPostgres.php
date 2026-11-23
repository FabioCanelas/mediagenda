<?php

namespace App\Support\BaseDeDatos;

use Illuminate\Support\Facades\DB;

/**
 * En PostgreSQL un trigger llama a una función PL/pgSQL. Esta clase crea las dos
 * piezas con el mismo nombre que el trigger de MariaDB (trg_...), y la función
 * con el prefijo fn_, para que la documentación sirva para los dos motores.
 *
 * Los errores usan el mismo código que en MariaDB (SQLSTATE 45000).
 */
class TriggersPostgres
{
    /**
     * @param  string  $evento  INSERT, UPDATE o DELETE.
     * @param  string  $cuerpo  Instrucciones PL/pgSQL; el RETURN se agrega solo.
     */
    public static function antesDe(string $evento, string $tabla, string $nombre, string $cuerpo): void
    {
        $retorno = $evento === 'DELETE' ? 'RETURN OLD;' : 'RETURN NEW;';

        DB::unprepared(<<<SQL
            CREATE OR REPLACE FUNCTION "fn_{$nombre}"() RETURNS trigger LANGUAGE plpgsql AS \$fn\$
            BEGIN
                {$cuerpo}
                {$retorno}
            END
            \$fn\$;

            DROP TRIGGER IF EXISTS "{$nombre}" ON "{$tabla}";
            CREATE TRIGGER "{$nombre}" BEFORE {$evento} ON "{$tabla}"
                FOR EACH ROW EXECUTE FUNCTION "fn_{$nombre}"();
            SQL);
    }

    public static function eliminar(string $tabla, string $nombre): void
    {
        DB::unprepared(<<<SQL
            DROP TRIGGER IF EXISTS "{$nombre}" ON "{$tabla}";
            DROP FUNCTION IF EXISTS "fn_{$nombre}"();
            SQL);
    }

    /**
     * Corta la operación con un mensaje para la aplicación.
     */
    public static function error(string $mensaje): string
    {
        $mensaje = str_replace("'", "''", $mensaje);

        return "RAISE EXCEPTION '{$mensaje}' USING ERRCODE = '45000';";
    }

    /**
     * Esquema activo de la conexión (mediagenda en Supabase). Las funciones con
     * SECURITY DEFINER fijan su search_path para que nadie las engañe con otro esquema.
     */
    public static function esquemaActual(): string
    {
        return (string) DB::selectOne('SELECT current_schema() AS esquema')->esquema;
    }
}
