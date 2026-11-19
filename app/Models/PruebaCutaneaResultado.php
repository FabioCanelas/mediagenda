<?php

namespace App\Models;

use Database\Factories\PruebaCutaneaResultadoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'pruebas_cutaneas_resultados')]
#[Fillable(['prueba_cutanea_id', 'alergeno_id', 'papula_mm', 'eritema_mm'])]
class PruebaCutaneaResultado extends Model
{
    /** @use HasFactory<PruebaCutaneaResultadoFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // El resultado positivo o negativo lo calcula el sistema, nunca se escribe a mano.
        static::saving(function (PruebaCutaneaResultado $resultado): void {
            $resultado->positivo = $resultado->pruebaCutanea->esPositivo((float) $resultado->papula_mm);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'papula_mm' => 'decimal:1',
            'eritema_mm' => 'decimal:1',
            'positivo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<PruebaCutanea, $this>
     */
    public function pruebaCutanea(): BelongsTo
    {
        return $this->belongsTo(PruebaCutanea::class);
    }

    /**
     * @return BelongsTo<Alergeno, $this>
     */
    public function alergeno(): BelongsTo
    {
        return $this->belongsTo(Alergeno::class);
    }
}
