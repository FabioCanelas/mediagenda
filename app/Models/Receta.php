<?php

namespace App\Models;

use App\Models\Concerns\RegistraAutor;
use Database\Factories\RecetaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Receta e indicaciones (HU-17). Mientras no se emite, la doctora la puede
 * corregir; al emitir el PDF queda fija y se guarda una copia.
 */
#[Fillable(['paciente_id', 'fecha', 'indicaciones'])]
class Receta extends Model
{
    /** @use HasFactory<RecetaFactory> */
    use HasFactory, HasUuids, RegistraAutor;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    /**
     * @return HasMany<RecetaMedicamento, $this>
     */
    public function medicamentos(): HasMany
    {
        return $this->hasMany(RecetaMedicamento::class)->orderBy('orden');
    }

    /**
     * @return HasOne<DocumentoEmitido, $this>
     */
    public function documento(): HasOne
    {
        return $this->hasOne(DocumentoEmitido::class);
    }

    public function estaEmitida(): bool
    {
        return $this->relationLoaded('documento') ? $this->documento !== null : $this->documento()->exists();
    }
}
