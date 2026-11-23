<?php

namespace App\Support\BaseDeDatos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Triggers de auditoría: cada INSERT, UPDATE o DELETE de una tabla clínica
 * deja una copia del registro antes y después del cambio en `auditoria`.
 *
 * El usuario que hizo el cambio lo define la aplicación en cada petición
 * (EstablecerUsuarioAuditoria): en MariaDB con la variable `@app_usuario_id` y en
 * PostgreSQL con el parámetro de sesión `app.usuario_id`.
 *
 * En MariaDB el trigger lista las columnas, así que si una tabla gana columnas nuevas
 * hay que volver a crearlo. En PostgreSQL una sola función copia la fila completa
 * (to_jsonb) y quita las columnas excluidas, que recibe como argumentos del trigger.
 */
class Auditoria
{
    /**
     * @param  array<int, string>  $excluir  Columnas que nunca deben copiarse (claves, secretos).
     */
    public static function crearTriggers(string $tabla, array $excluir = []): void
    {
        if (Restricciones::esPostgres()) {
            self::crearTriggersPostgres($tabla, $excluir);

            return;
        }

        if (! Restricciones::esMysql()) {
            return;
        }

        self::eliminarTriggers($tabla);

        $columnas = array_values(array_diff(Schema::getColumnListing($tabla), $excluir));
        $nuevo = self::objetoJson($columnas, 'NEW');
        $anterior = self::objetoJson($columnas, 'OLD');
        $usaBorradoLogico = in_array('deleted_at', $columnas, true);

        $accionUpdate = $usaBorradoLogico
            ? "CASE WHEN OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN 'BORRADO' WHEN OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN 'RESTAURADO' ELSE 'UPDATE' END"
            : "'UPDATE'";

        DB::unprepared(<<<SQL
            CREATE TRIGGER `trg_{$tabla}_auditoria_insert` AFTER INSERT ON `{$tabla}` FOR EACH ROW
            INSERT INTO auditoria (tabla, registro_id, accion, datos_antes, datos_despues, usuario_id, realizado_en)
            VALUES ('{$tabla}', NEW.id, 'INSERT', NULL, {$nuevo}, @app_usuario_id, NOW(6))
            SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER `trg_{$tabla}_auditoria_update` AFTER UPDATE ON `{$tabla}` FOR EACH ROW
            INSERT INTO auditoria (tabla, registro_id, accion, datos_antes, datos_despues, usuario_id, realizado_en)
            VALUES ('{$tabla}', NEW.id, {$accionUpdate}, {$anterior}, {$nuevo}, @app_usuario_id, NOW(6))
            SQL);

        DB::unprepared(<<<SQL
            CREATE TRIGGER `trg_{$tabla}_auditoria_delete` AFTER DELETE ON `{$tabla}` FOR EACH ROW
            INSERT INTO auditoria (tabla, registro_id, accion, datos_antes, datos_despues, usuario_id, realizado_en)
            VALUES ('{$tabla}', OLD.id, 'DELETE', {$anterior}, NULL, @app_usuario_id, NOW(6))
            SQL);
    }

    public static function eliminarTriggers(string $tabla): void
    {
        foreach (['insert', 'update', 'delete'] as $evento) {
            if (Restricciones::esMysql()) {
                DB::unprepared("DROP TRIGGER IF EXISTS `trg_{$tabla}_auditoria_{$evento}`");
            }

            if (Restricciones::esPostgres()) {
                DB::unprepared("DROP TRIGGER IF EXISTS \"trg_{$tabla}_auditoria_{$evento}\" ON \"{$tabla}\"");
            }
        }
    }

    /**
     * Función única de auditoría para PostgreSQL. Corre con los permisos de su dueño
     * (SECURITY DEFINER): el usuario de la aplicación solo puede LEER la auditoría,
     * pero sus cambios igual quedan registrados.
     */
    public static function crearFuncionPostgres(): void
    {
        $esquema = TriggersPostgres::esquemaActual();

        DB::unprepared(<<<SQL
            CREATE OR REPLACE FUNCTION "fn_auditoria"() RETURNS trigger
            LANGUAGE plpgsql SECURITY DEFINER SET search_path = "{$esquema}", pg_temp AS \$fn\$
            DECLARE
                excluir text[] := COALESCE(TG_ARGV, '{}'::text[]);
                antes jsonb;
                despues jsonb;
                accion text := TG_OP;
            BEGIN
                IF TG_OP IN ('UPDATE', 'DELETE') THEN
                    antes := to_jsonb(OLD) - excluir;
                END IF;

                IF TG_OP IN ('INSERT', 'UPDATE') THEN
                    despues := to_jsonb(NEW) - excluir;
                END IF;

                IF TG_OP = 'UPDATE' AND antes ? 'deleted_at' THEN
                    IF antes->>'deleted_at' IS NULL AND despues->>'deleted_at' IS NOT NULL THEN
                        accion := 'BORRADO';
                    ELSIF antes->>'deleted_at' IS NOT NULL AND despues->>'deleted_at' IS NULL THEN
                        accion := 'RESTAURADO';
                    END IF;
                END IF;

                INSERT INTO auditoria (tabla, registro_id, accion, datos_antes, datos_despues, usuario_id, realizado_en)
                VALUES (
                    TG_TABLE_NAME,
                    COALESCE(despues->>'id', antes->>'id'),
                    accion,
                    antes::json,
                    despues::json,
                    NULLIF(current_setting('app.usuario_id', true), '')::bigint,
                    now()
                );

                RETURN NULL;
            END
            \$fn\$;

            REVOKE ALL ON FUNCTION "fn_auditoria"() FROM PUBLIC;
            SQL);
    }

    /**
     * @param  array<int, string>  $excluir
     */
    private static function crearTriggersPostgres(string $tabla, array $excluir): void
    {
        self::eliminarTriggers($tabla);

        $argumentos = implode(', ', array_map(fn (string $columna): string => "'{$columna}'", $excluir));

        foreach (['insert' => 'INSERT', 'update' => 'UPDATE', 'delete' => 'DELETE'] as $sufijo => $evento) {
            DB::unprepared(<<<SQL
                CREATE TRIGGER "trg_{$tabla}_auditoria_{$sufijo}" AFTER {$evento} ON "{$tabla}"
                    FOR EACH ROW EXECUTE FUNCTION "fn_auditoria"({$argumentos})
                SQL);
        }
    }

    /**
     * @param  array<int, string>  $columnas
     */
    private static function objetoJson(array $columnas, string $fila): string
    {
        $pares = array_map(fn (string $columna): string => "'{$columna}', {$fila}.`{$columna}`", $columnas);

        return 'JSON_OBJECT('.implode(', ', $pares).')';
    }
}
