<?php

namespace App\Policies;

use App\Models\Cita;
use App\Models\User;

/**
 * Agenda: doctora y recepción. Las citas no se borran: se cancelan con un motivo.
 */
class CitaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Cita $cita): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Cita $cita): bool
    {
        return true;
    }

    public function delete(User $user, Cita $cita): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Cita $cita): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, Cita $cita): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
