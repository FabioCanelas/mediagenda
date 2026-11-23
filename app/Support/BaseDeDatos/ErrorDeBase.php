<?php

namespace App\Support\BaseDeDatos;

use Illuminate\Database\QueryException;

/**
 * Mensaje legible de un error lanzado por un trigger. MariaDB entrega solo el texto;
 * PostgreSQL le antepone "ERROR:" y agrega líneas de contexto (CONTEXT: PL/pgSQL…).
 */
class ErrorDeBase
{
    public static function mensaje(QueryException $error, string $porDefecto = 'La base de datos rechazó la operación.'): string
    {
        $mensaje = (string) ($error->errorInfo[2] ?? '');
        $mensaje = (string) preg_replace('/^ERROR:\s*/', '', strtok($mensaje, "\n") ?: '');

        return trim($mensaje) === '' ? $porDefecto : trim($mensaje);
    }
}
