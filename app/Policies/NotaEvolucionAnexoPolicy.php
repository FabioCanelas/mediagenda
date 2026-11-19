<?php

namespace App\Policies;

use App\Models\NotaEvolucionAnexo;
use App\Models\User;

/**
 * Anexos de corrección: la doctora los agrega; nunca se modifican ni se borran.
 */
class NotaEvolucionAnexoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, NotaEvolucionAnexo $notaEvolucionAnexo): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, NotaEvolucionAnexo $notaEvolucionAnexo): bool
    {
        return false;
    }

    public function delete(User $user, NotaEvolucionAnexo $notaEvolucionAnexo): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, NotaEvolucionAnexo $notaEvolucionAnexo): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, NotaEvolucionAnexo $notaEvolucionAnexo): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
