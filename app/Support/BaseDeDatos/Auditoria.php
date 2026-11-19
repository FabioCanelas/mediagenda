<?php

namespace App\Support\BaseDeDatos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Triggers de auditoría: cada INSERT, UPDATE o DELETE de una tabla clínica
 * deja una copia del registro antes y después del cambio en `auditoria`.
 *
 * El usuario que hizo el cambio llega en la variable de sesión `@app_usuario_id`,
 * que la aplicación define en cada petición (EstablecerUsuarioAuditoria).
 * Si una tabla gana columnas nuevas, hay que volver a crear sus triggers.
 */
class Auditoria
{
    /**
     * @param  array<int, string>  $excluir  Columnas que nunca deben copiarse (claves, secretos).
     */
    public static function crearTriggers(string $tabla, array $excluir = []): void
    {
        if (! Restricciones::soportado()) {
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
        if (! Restricciones::soportado()) {
            return;
        }

        foreach (['insert', 'update', 'delete'] as $evento) {
            DB::unprepared("DROP TRIGGER IF EXISTS `trg_{$tabla}_auditoria_{$evento}`");
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
