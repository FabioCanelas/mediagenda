<?php

namespace App\Models;

use App\Enums\Brazo;
use App\Enums\FaseInmunoterapia;
use App\Enums\ReaccionLocal;
use Carbon\CarbonInterface;
use Database\Factories\AplicacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Aplicación de una dosis (HU-11). Al crearla se fija el fin de los
 * 30 minutos de observación y se marca si la dosis difiere del esquema.
 */
#[Table(name: 'aplicaciones')]
#[Fillable([
    'inmunoterapia_id', 'frasco_id', 'esquema_dosis_id', 'aplicada_en', 'volumen_ml',
    'brazo', 'reaccion_local', 'reaccion_local_mm', 'notas',
])]
class Aplicacion extends Model
{
    /** @use HasFactory<AplicacionFactory> */
    use HasFactory, HasUuids;

    public const MINUTOS_OBSERVACION = 30;

    protected $attributes = [
        'reaccion_local' => 'ninguna',
    ];

    protected static function booted(): void
    {
        static::creating(function (Aplicacion $aplicacion): void {
            $aplicacion->aplicada_en ??= now();
            $aplicacion->aplicada_por ??= Auth::id();
            $aplicacion->fase ??= $aplicacion->inmunoterapia->fase;
            $aplicacion->observacion_hasta = $aplicacion->aplicada_en->copy()->addMinutes(self::MINUTOS_OBSERVACION);
            $aplicacion->dosis_difiere_esquema = $aplicacion->difiereDelEsquema();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fase' => FaseInmunoterapia::class,
            'aplicada_en' => 'datetime',
            'volumen_ml' => 'decimal:2',
            'dosis_difiere_esquema' => 'boolean',
            'brazo' => Brazo::class,
            'reaccion_local' => ReaccionLocal::class,
            'reaccion_local_mm' => 'decimal:1',
            'observacion_hasta' => 'datetime',
            'observacion_completada_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Inmunoterapia, $this>
     */
    public function inmunoterapia(): BelongsTo
    {
        return $this->belongsTo(Inmunoterapia::class);
    }

    /**
     * @return BelongsTo<Frasco, $this>
     */
    public function frasco(): BelongsTo
    {
        return $this->belongsTo(Frasco::class);
    }

    /**
     * @return BelongsTo<EsquemaDosis, $this>
     */
    public function esquemaDosis(): BelongsTo
    {
        return $this->belongsTo(EsquemaDosis::class);
    }

    /**
     * @return HasOne<ReaccionSistemica, $this>
     */
    public function reaccionSistemica(): HasOne
    {
        return $this->hasOne(ReaccionSistemica::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function aplicadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aplicada_por');
    }

    /**
     * Compara el volumen aplicado con el del paso del esquema o la dosis de mantenimiento.
     */
    public function difiereDelEsquema(): bool
    {
        $esperado = $this->esquemaDosis?->volumen_ml
            ?? ($this->fase === FaseInmunoterapia::Mantenimiento ? $this->inmunoterapia->dosis_mantenimiento_ml : null);

        return $esperado !== null && abs((float) $esperado - (float) $this->volumen_ml) > 0.001;
    }

    public function observacionCompletada(): bool
    {
        return $this->observacion_completada_en !== null;
    }

    public function puedeTerminarObservacion(?CarbonInterface $ahora = null): bool
    {
        return ! $this->observacionCompletada() && ($ahora ?? now())->gte($this->observacion_hasta);
    }

    public function terminarObservacion(): void
    {
        if (! $this->puedeTerminarObservacion()) {
            throw new LogicException('Todavía no terminan los 30 minutos de observación.');
        }

        $this->forceFill(['observacion_completada_en' => now()])->save();
    }

    /**
     * Fecha sugerida para la siguiente aplicación según el intervalo del paso o del mantenimiento.
     */
    public function fechaSiguienteSugerida(): CarbonInterface
    {
        $dias = $this->esquemaDosis?->intervalo_dias
            ?? ($this->inmunoterapia->intervalo_mantenimiento_dias ?? 28);

        return $this->aplicada_en->copy()->addDays($dias)->startOfDay();
    }
}
