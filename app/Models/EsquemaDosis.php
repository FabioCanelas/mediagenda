<?php

namespace App\Models;

use App\Enums\Dilucion;
use Database\Factories\EsquemaDosisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un paso de la fase de inicio: frasco (dilución), volumen y días hasta la siguiente dosis.
 */
#[Table(name: 'esquema_dosis')]
#[Fillable(['inmunoterapia_id', 'paso', 'dilucion', 'volumen_ml', 'intervalo_dias'])]
class EsquemaDosis extends Model
{
    /** @use HasFactory<EsquemaDosisFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dilucion' => Dilucion::class,
            'volumen_ml' => 'decimal:2',
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
     * @return HasMany<Aplicacion, $this>
     */
    public function aplicaciones(): HasMany
    {
        return $this->hasMany(Aplicacion::class);
    }

    public function descripcion(): string
    {
        return "Paso {$this->paso}: {$this->volumen_ml} mL del frasco {$this->dilucion->getLabel()}";
    }
}
