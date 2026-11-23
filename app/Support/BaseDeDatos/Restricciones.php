<?php

namespace App\Support\BaseDeDatos;

use Illuminate\Support\Facades\DB;

/**
 * Restricciones CHECK a nivel de base de datos.
 *
 * Las reglas viven en la base además de la aplicación, para que ningún
 * cliente (phpMyAdmin, un script o un error de código) guarde datos inválidos.
 * Funcionan en MariaDB (local) y en PostgreSQL (Supabase); en SQLite (pruebas) no se aplican.
 *
 * Las expresiones se escriben una sola vez con la sintaxis de MariaDB
 * (`columna`, REGEXP) y se traducen a PostgreSQL ("columna", ~) cuando corresponde.
 */
class Restricciones
{
    public static function soportado(): bool
    {
        return self::esMysql() || self::esPostgres();
    }

    public static function esMysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    public static function esPostgres(): bool
    {
        return DB::getDriverName() === 'pgsql';
    }

    public static function agregar(string $tabla, string $nombre, string $expresion): void
    {
        if (self::esMysql()) {
            DB::statement("ALTER TABLE `{$tabla}` ADD CONSTRAINT `{$nombre}` CHECK ({$expresion})");
        }

        if (self::esPostgres()) {
            $expresion = self::traducirAPostgres($expresion);

            DB::statement("ALTER TABLE \"{$tabla}\" ADD CONSTRAINT \"{$nombre}\" CHECK ({$expresion})");
        }
    }

    public static function quitar(string $tabla, string $nombre): void
    {
        if (self::esMysql()) {
            DB::statement("ALTER TABLE `{$tabla}` DROP CONSTRAINT IF EXISTS `{$nombre}`");
        }

        if (self::esPostgres()) {
            DB::statement("ALTER TABLE \"{$tabla}\" DROP CONSTRAINT IF EXISTS \"{$nombre}\"");
        }
    }

    /**
     * Convierte una expresión escrita para MariaDB a PostgreSQL:
     * identificadores entre comillas dobles y expresiones regulares con el operador ~.
     */
    public static function traducirAPostgres(string $expresion): string
    {
        $expresion = str_replace('`', '"', $expresion);

        return (string) preg_replace('/\s+REGEXP\s+/i', ' ~ ', $expresion);
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
