<?php

namespace App\Policies;

use App\Models\Alergia;
use App\Models\User;

/**
 * Alergias del paciente: solo la doctora. Una alergia errónea se marca como descartada, no se borra.
 */
class AlergiaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, Alergia $alergia): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, Alergia $alergia): bool
    {
        return $user->esDoctora();
    }

    public function delete(User $user, Alergia $alergia): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Alergia $alergia): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, Alergia $alergia): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
