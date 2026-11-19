<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Completa created_by y updated_by con el usuario que inició sesión.
 *
 * @mixin Model
 */
trait RegistraAutor
{
    public static function bootRegistraAutor(): void
    {
        static::creating(function (Model $modelo): void {
            $usuarioId = Auth::id();

            if ($usuarioId === null) {
                return;
            }

            $modelo->setAttribute('created_by', $modelo->getAttribute('created_by') ?? $usuarioId);

            if (static::registraActualizador()) {
                $modelo->setAttribute('updated_by', $usuarioId);
            }
        });

        static::updating(function (Model $modelo): void {
            $usuarioId = Auth::id();

            if ($usuarioId !== null && static::registraActualizador()) {
                $modelo->setAttribute('updated_by', $usuarioId);
            }
        });
    }

    /**
     * Las tablas sin columna updated_by sobrescriben este método y devuelven false.
     */
    protected static function registraActualizador(): bool
    {
        return true;
    }
}
