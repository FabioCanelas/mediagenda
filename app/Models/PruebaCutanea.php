<?php

namespace App\Models;

use Database\Factories\PruebaCutaneaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * Prick test. Reglas de lectura (el criterio lo define la doctora, 3 mm por defecto):
 *  - La prueba es interpretable si la histamina reacciona (pápula >= criterio)
 *    y la solución salina no (pápula < criterio).
 *  - Un alérgeno es positivo si su pápula supera al control negativo en el criterio o más.
 */
#[Table(name: 'pruebas_cutaneas')]
#[Fillable([
    'paciente_id', 'fecha', 'control_positivo_mm', 'control_negativo_mm', 'criterio_positividad_mm',
    'minutos_lectura', 'antihistaminicos_suspendidos', 'observaciones',
])]
class PruebaCutanea extends Model
{
    /** @use HasFactory<PruebaCutaneaFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public const CRITERIO_POR_DEFECTO_MM = 3.0;

    protected $attributes = [
        'criterio_positividad_mm' => self::CRITERIO_POR_DEFECTO_MM,
        'minutos_lectura' => 15,
    ];

    protected static function booted(): void
    {
        static::creating(function (PruebaCutanea $prueba): void {
            $prueba->realizado_por ??= Auth::id();
        });

        static::saving(function (PruebaCutanea $prueba): void {
            $prueba->interpretable = $prueba->esInterpretable();
        });

        // Si cambian los controles o el criterio, se recalculan los resultados ya guardados.
        static::saved(function (PruebaCutanea $prueba): void {
            if ($prueba->wasChanged(['control_positivo_mm', 'control_negativo_mm', 'criterio_positividad_mm'])) {
                $prueba->resultados()->each(fn (PruebaCutaneaResultado $resultado) => $resultado->save());
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'control_positivo_mm' => 'decimal:1',
            'control_negativo_mm' => 'decimal:1',
            'criterio_positividad_mm' => 'decimal:1',
            'antihistaminicos_suspendidos' => 'boolean',
            'interpretable' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function realizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'realizado_por');
    }

    /**
     * @return HasMany<PruebaCutaneaResultado, $this>
     */
    public function resultados(): HasMany
    {
        return $this->hasMany(PruebaCutaneaResultado::class);
    }

    public function esInterpretable(): bool
    {
        $criterio = (float) $this->criterio_positividad_mm;

        return (float) $this->control_positivo_mm >= $criterio
            && (float) $this->control_negativo_mm < $criterio;
    }

    public function esPositivo(float $papulaMm): bool
    {
        return $this->esInterpretable()
            && ($papulaMm - (float) $this->control_negativo_mm) >= (float) $this->criterio_positividad_mm;
    }

    /**
     * Motivo por el que la prueba no se puede interpretar, o null si se puede.
     */
    public function motivoNoInterpretable(): ?string
    {
        $criterio = (float) $this->criterio_positividad_mm;

        return match (true) {
            (float) $this->control_positivo_mm < $criterio => 'El control positivo (histamina) no reaccionó: puede haber antihistamínicos.',
            (float) $this->control_negativo_mm >= $criterio => 'El control negativo reaccionó: posible dermografismo.',
            default => null,
        };
    }
}
