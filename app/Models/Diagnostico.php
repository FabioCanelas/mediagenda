<?php

namespace App\Models;

use App\Enums\TipoDiagnostico;
use App\Models\Concerns\RegistraAutor;
use Database\Factories\DiagnosticoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['historia_clinica_id', 'cie10_codigo', 'descripcion', 'tipo'])]
class Diagnostico extends Model
{
    /** @use HasFactory<DiagnosticoFactory> */
    use HasFactory, HasUuids, RegistraAutor, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoDiagnostico::class,
        ];
    }

    /**
     * @return BelongsTo<HistoriaClinica, $this>
     */
    public function historiaClinica(): BelongsTo
    {
        return $this->belongsTo(HistoriaClinica::class);
    }

    /**
     * @return BelongsTo<Cie10, $this>
     */
    public function cie10(): BelongsTo
    {
        return $this->belongsTo(Cie10::class, 'cie10_codigo', 'codigo');
    }

    public function tieneCodigo(): bool
    {
        return $this->cie10_codigo !== null;
    }

    protected static function registraActualizador(): bool
    {
        return false;
    }
}
