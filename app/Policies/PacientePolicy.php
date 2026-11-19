<?php

namespace App\Policies;

use App\Models\Paciente;
use App\Models\User;

/**
 * Doctora y recepción registran y consultan pacientes. Solo la doctora puede ocultar (borrado lógico) o restaurar un paciente.
 */
class PacientePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Paciente $paciente): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Paciente $paciente): bool
    {
        return true;
    }

    public function delete(User $user, Paciente $paciente): bool
    {
        return $user->esDoctora();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Paciente $paciente): bool
    {
        return $user->esDoctora();
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, Paciente $paciente): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
