<?php

namespace App\Models;

use App\Enums\FaseInmunoterapia;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fila de la vista SQL v_proxima_aplicacion (solo lectura): cuándo le toca la
 * siguiente dosis a cada inmunoterapia subcutánea activa.
 */
#[Table(name: 'v_proxima_aplicacion', key: 'inmunoterapia_id', keyType: 'string', incrementing: false, timestamps: false)]
class ProximaAplicacion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fase' => FaseInmunoterapia::class,
            'ultima_aplicacion' => 'datetime',
            'fecha_prevista' => 'date',
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
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    /**
     * Días de atraso respecto de la fecha prevista (negativo si todavía falta).
     */
    public function diasDeAtraso(?CarbonInterface $hoy = null): int
    {
        return (int) $this->fecha_prevista->copy()->startOfDay()->diffInDays(($hoy ?? now())->copy()->startOfDay(), false);
    }
}
