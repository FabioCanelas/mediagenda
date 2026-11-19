<?php

namespace App\Policies;

use App\Models\DocumentoEmitido;
use App\Models\User;

/**
 * Documentos emitidos: la doctora los ve y reimprime. Nunca se modifican ni se borran.
 */
class DocumentoEmitidoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esDoctora();
    }

    public function view(User $user, DocumentoEmitido $documentoEmitido): bool
    {
        return $user->esDoctora();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, DocumentoEmitido $documentoEmitido): bool
    {
        return false;
    }

    public function delete(User $user, DocumentoEmitido $documentoEmitido): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, DocumentoEmitido $documentoEmitido): bool
    {
        return false;
    }

    /**
     * Nadie borra datos de forma definitiva; además, el usuario de la base no tiene DELETE.
     */
    public function forceDelete(User $user, DocumentoEmitido $documentoEmitido): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
