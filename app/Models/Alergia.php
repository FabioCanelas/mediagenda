<?php

namespace App\Models;

use App\Enums\CategoriaAlergia;
use App\Enums\CriticidadAlergia;
use App\Enums\EstadoAlergia;
use App\Enums\GravedadReaccion;
use App\Enums\TipoAlergia;
use App\Models\Concerns\RegistraAutor;
use Database\Factories\AlergiaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Alergia o intolerancia del paciente (modelo AllergyIntolerance de HL7 FHIR).
 */
#[Fillable([
    'paciente_id', 'sustancia', 'categoria', 'tipo', 'criticidad', 'estado',
    'reaccion', 'gravedad', 'fecha_inicio', 'notas',
])]
class Alergia extends Model
{
    /** @use HasFactory<AlergiaFactory> */
    use HasFactory, HasUuids, RegistraAutor, SoftDeletes;

    protected $attributes = [
        'tipo' => 'alergia',
        'criticidad' => 'no_evaluable',
        'estado' => 'sospechada',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categoria' => CategoriaAlergia::class,
            'tipo' => TipoAlergia::class,
            'criticidad' => CriticidadAlergia::class,
            'estado' => EstadoAlergia::class,
            'gravedad' => GravedadReaccion::class,
            'fecha_inicio' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function estaVigente(): bool
    {
        return $this->estado !== EstadoAlergia::Descartada;
    }
}
