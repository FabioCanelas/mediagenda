<?php

namespace App\Policies;

use App\Models\NotaEvolucion;
use App\Models\User;

/**
 * Notas de evolución: solo la doctora. Una nota cerrada ya no se edita (la base también lo impide) y ninguna se borra.
 */
class NotaEvolucionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, NotaEvolucion $notaEvolucion): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, NotaEvolucion $notaEvolucion): bool
    {
        return $user->esDoctora() && ! $notaEvolucion->estaCerrada();
    }

    public function delete(User $user, NotaEvolucion $notaEvolucion): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, NotaEvolucion $notaEvolucion): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, NotaEvolucion $notaEvolucion): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
