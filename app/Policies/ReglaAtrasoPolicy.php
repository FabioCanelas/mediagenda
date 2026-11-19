<?php

namespace App\Policies;

use App\Models\ReglaAtraso;
use App\Models\User;

/**
 * Reglas de atraso: solo la doctora. Una regla que ya no se usa se desactiva.
 */
class ReglaAtrasoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, ReglaAtraso $reglaAtraso): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, ReglaAtraso $reglaAtraso): bool
    {
        return $user->esDoctora();
    }

    public function delete(User $user, ReglaAtraso $reglaAtraso): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, ReglaAtraso $reglaAtraso): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, ReglaAtraso $reglaAtraso): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
