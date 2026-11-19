<?php

namespace App\Models;

use App\Models\Concerns\RegistraAutor;
use Database\Factories\NotaEvolucionAnexoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Corrección de una nota cerrada. Solo se crea: no tiene fecha de modificación
 * y el usuario de la base no puede actualizarla.
 */
#[Table(name: 'notas_evolucion_anexos')]
#[Fillable(['nota_evolucion_id', 'texto'])]
class NotaEvolucionAnexo extends Model
{
    /** @use HasFactory<NotaEvolucionAnexoFactory> */
    use HasFactory, HasUuids, RegistraAutor;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<NotaEvolucion, $this>
     */
    public function nota(): BelongsTo
    {
        return $this->belongsTo(NotaEvolucion::class, 'nota_evolucion_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function registraActualizador(): bool
    {
        return false;
    }
}
