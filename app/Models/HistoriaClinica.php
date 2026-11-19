<?php

namespace App\Models;

use App\Models\Concerns\RegistraAutor;
use Database\Factories\HistoriaClinicaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Historia clínica con los apartados de la NOM-004-SSA3-2012.
 * Cada cambio queda guardado en la tabla `auditoria` (trigger de la base).
 */
#[Table(name: 'historias_clinicas')]
#[Fillable([
    'paciente_id',
    'antecedentes_heredofamiliares', 'antecedentes_personales_patologicos', 'antecedentes_personales_no_patologicos',
    'padecimiento_actual', 'interrogatorio_aparatos_sistemas',
    'habitus_exterior', 'presion_sistolica', 'presion_diastolica', 'frecuencia_cardiaca', 'frecuencia_respiratoria',
    'temperatura', 'saturacion_oxigeno', 'peso_kg', 'talla_cm', 'exploracion_regional',
    'resultados_estudios', 'pronostico', 'indicacion_terapeutica',
])]
class HistoriaClinica extends Model
{
    /** @use HasFactory<HistoriaClinicaFactory> */
    use HasFactory, HasUuids, RegistraAutor;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'temperatura' => 'decimal:1',
            'peso_kg' => 'decimal:2',
            'talla_cm' => 'decimal:1',
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
     * @return HasMany<Diagnostico, $this>
     */
    public function diagnosticos(): HasMany
    {
        return $this->hasMany(Diagnostico::class);
    }

    /**
     * Índice de masa corporal calculado con el peso y la talla registrados.
     */
    public function imc(): ?float
    {
        if (! $this->peso_kg || ! $this->talla_cm) {
            return null;
        }

        $tallaMetros = (float) $this->talla_cm / 100;

        return round((float) $this->peso_kg / ($tallaMetros * $tallaMetros), 1);
    }
}
