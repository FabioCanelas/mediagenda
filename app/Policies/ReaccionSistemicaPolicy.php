<?php

namespace App\Policies;

use App\Models\ReaccionSistemica;
use App\Models\User;

/**
 * Reacciones sistémicas: solo la doctora. No se borran.
 */
class ReaccionSistemicaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, ReaccionSistemica $reaccionSistemica): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, ReaccionSistemica $reaccionSistemica): bool
    {
        return $user->esDoctora();
    }

    public function delete(User $user, ReaccionSistemica $reaccionSistemica): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, ReaccionSistemica $reaccionSistemica): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, ReaccionSistemica $reaccionSistemica): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
