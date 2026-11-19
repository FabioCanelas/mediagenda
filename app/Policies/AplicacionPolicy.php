<?php

namespace App\Policies;

use App\Models\Aplicacion;
use App\Models\User;

/**
 * Aplicaciones: solo la doctora. No se borran.
 */
class AplicacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, Aplicacion $aplicacion): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, Aplicacion $aplicacion): bool
    {
        return $user->esDoctora();
    }

    public function delete(User $user, Aplicacion $aplicacion): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Aplicacion $aplicacion): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, Aplicacion $aplicacion): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
