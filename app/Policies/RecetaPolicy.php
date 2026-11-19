<?php

namespace App\Policies;

use App\Models\Receta;
use App\Models\User;

/**
 * Recetas: solo la doctora. Una receta emitida ya no se modifica.
 */
class RecetaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, Receta $receta): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, Receta $receta): bool
    {
        return $user->esDoctora() && ! $receta->estaEmitida();
    }

    /**
     * Generar el PDF con membrete. Se emite una sola vez; después se reimprime la copia.
     */
    public function emitir(User $user, Receta $receta): bool
    {
        return $user->esDoctora() && ! $receta->estaEmitida() && $receta->medicamentos()->exists();
    }

    public function delete(User $user, Receta $receta): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Receta $receta): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, Receta $receta): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
