<?php

namespace App\Policies;

use App\Models\CuestionarioAct;
use App\Models\User;

/**
 * Cuestionario ACT: solo la doctora.
 */
class CuestionarioActPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, CuestionarioAct $cuestionarioAct): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, CuestionarioAct $cuestionarioAct): bool
    {
        return $user->esDoctora();
    }

    public function delete(User $user, CuestionarioAct $cuestionarioAct): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, CuestionarioAct $cuestionarioAct): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, CuestionarioAct $cuestionarioAct): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
