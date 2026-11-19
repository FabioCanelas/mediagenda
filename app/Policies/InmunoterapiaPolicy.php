<?php

namespace App\Policies;

use App\Models\Inmunoterapia;
use App\Models\User;

/**
 * Inmunoterapia: solo la doctora. No se borra: se suspende o se finaliza.
 */
class InmunoterapiaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, Inmunoterapia $inmunoterapia): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return $user->esDoctora();
    }

    public function update(User $user, Inmunoterapia $inmunoterapia): bool
    {
        return $user->esDoctora();
    }

    public function delete(User $user, Inmunoterapia $inmunoterapia): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Inmunoterapia $inmunoterapia): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, Inmunoterapia $inmunoterapia): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
