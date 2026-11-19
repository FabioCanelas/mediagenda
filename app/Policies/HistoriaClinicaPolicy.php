<?php

namespace App\Policies;

use App\Models\HistoriaClinica;
use App\Models\User;

/**
 * La historia clínica es solo para la doctora. No se borra nunca.
 */
class HistoriaClinicaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, HistoriaClinica $historiaClinica): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, HistoriaClinica $historiaClinica): bool
    {
        return $user->esDoctora();
    }

    public function delete(User $user, HistoriaClinica $historiaClinica): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, HistoriaClinica $historiaClinica): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, HistoriaClinica $historiaClinica): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
