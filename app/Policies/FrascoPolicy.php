<?php

namespace App\Policies;

use App\Models\Frasco;
use App\Models\User;

/**
 * Frascos: solo la doctora. Un frasco que ya no se usa se da de baja.
 */
class FrascoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, Frasco $frasco): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, Frasco $frasco): bool
    {
        return $user->esDoctora();
    }

    public function delete(User $user, Frasco $frasco): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Frasco $frasco): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, Frasco $frasco): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
