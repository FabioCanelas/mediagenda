<?php

namespace App\Policies;

use App\Models\Consentimiento;
use App\Models\User;

/**
 * Consentimiento informado: solo la doctora. Después de emitirlo solo se registra la firma (habilidad firmar).
 */
class ConsentimientoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, Consentimiento $consentimiento): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, Consentimiento $consentimiento): bool
    {
        return false;
    }

    /**
     * Registrar que el paciente (o su tutor) firmó el documento impreso.
     */
    public function firmar(User $user, Consentimiento $consentimiento): bool
    {
        return $user->esDoctora() && ! $consentimiento->estaFirmado();
    }

    public function delete(User $user, Consentimiento $consentimiento): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Consentimiento $consentimiento): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, Consentimiento $consentimiento): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
