<?php

namespace App\Policies;

use App\Models\Preconsulta;
use App\Models\User;

/**
 * Pre-consulta: la recepción prepara la tablet; solo la doctora ve y revisa las respuestas.
 */
class PreconsultaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, Preconsulta $preconsulta): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Preconsulta $preconsulta): bool
    {
        return $user->esDoctora() && $preconsulta->estaPorRevisar();
    }

    public function delete(User $user, Preconsulta $preconsulta): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Preconsulta $preconsulta): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, Preconsulta $preconsulta): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
