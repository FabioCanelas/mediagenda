<?php

namespace App\Support\BaseDeDatos;

use Illuminate\Support\Facades\DB;

/**
 * Restricciones CHECK a nivel de base de datos.
 *
 * Las reglas viven en la base además de la aplicación, para que ningún
 * cliente (phpMyAdmin, un script o un error de código) guarde datos inválidos.
 * En SQLite (pruebas) no se aplican.
 */
class Restricciones
{
    public static function soportado(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    public static function agregar(string $tabla, string $nombre, string $expresion): void
    {
        if (! self::soportado()) {
            return;
        }

        DB::statement("ALTER TABLE `{$tabla}` ADD CONSTRAINT `{$nombre}` CHECK ({$expresion})");
    }

    public static function quitar(string $tabla, string $nombre): void
    {
        if (! self::soportado()) {
            return;
        }

        DB::statement("ALTER TABLE `{$tabla}` DROP CONSTRAINT IF EXISTS `{$nombre}`");
    }

    /**
     * @param  array<int, string>  $valores
     */
    public static function enLista(string $columna, array $valores, bool $admiteNulo = false): string
    {
        $lista = implode(', ', array_map(fn (string $valor): string => DB::getPdo()->quote($valor), $valores));
        $condicion = "`{$columna}` IN ({$lista})";

        return $admiteNulo ? "(`{$columna}` IS NULL OR {$condicion})" : $condicion;
    }

    public static function entre(string $columna, int|float $minimo, int|float $maximo, bool $admiteNulo = true): string
    {
        $condicion = "`{$columna}` BETWEEN {$minimo} AND {$maximo}";

        return $admiteNulo ? "(`{$columna}` IS NULL OR {$condicion})" : $condicion;
    }
}
