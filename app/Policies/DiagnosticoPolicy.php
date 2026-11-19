<?php

namespace App\Policies;

use App\Models\Diagnostico;
use App\Models\User;

/**
 * Diagnósticos de la historia clínica: solo la doctora. Quitar un diagnóstico es un borrado lógico.
 */
class DiagnosticoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, Diagnostico $diagnostico): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, Diagnostico $diagnostico): bool
    {
        return $user->esDoctora();
    }

    public function delete(User $user, Diagnostico $diagnostico): bool
    {
        return $user->esDoctora();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Diagnostico $diagnostico): bool
    {
        return $user->esDoctora();
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, Diagnostico $diagnostico): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
