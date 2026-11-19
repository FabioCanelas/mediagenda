<?php

namespace App\Policies;

use App\Models\Alergeno;
use App\Models\User;

/**
 * Panel de alérgenos: solo la doctora. Un alérgeno que ya no se usa se desactiva.
 */
class AlergenoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, Alergeno $alergeno): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, Alergeno $alergeno): bool
    {
        return $user->esDoctora();
    }

    public function delete(User $user, Alergeno $alergeno): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Alergeno $alergeno): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, Alergeno $alergeno): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
