<?php

namespace App\Policies;

use App\Models\Adjunto;
use App\Models\User;

/**
 * Estudios adjuntos: solo la doctora. El archivo no se reemplaza; se corrigen el tipo, la fecha o la descripción.
 */
class AdjuntoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, Adjunto $adjunto): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, Adjunto $adjunto): bool
    {
        return $user->esDoctora();
    }

    public function delete(User $user, Adjunto $adjunto): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Adjunto $adjunto): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, Adjunto $adjunto): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
