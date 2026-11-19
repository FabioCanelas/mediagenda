<?php

namespace App\Policies;

use App\Models\PruebaCutanea;
use App\Models\User;

/**
 * Prick test: solo la doctora. Las pruebas no se borran.
 */
class PruebaCutaneaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, PruebaCutanea $pruebaCutanea): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, PruebaCutanea $pruebaCutanea): bool
    {
        return $user->esDoctora();
    }

    public function delete(User $user, PruebaCutanea $pruebaCutanea): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, PruebaCutanea $pruebaCutanea): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, PruebaCutanea $pruebaCutanea): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
