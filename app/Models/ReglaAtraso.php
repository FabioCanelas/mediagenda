<?php

namespace App\Models;

use App\Enums\FaseInmunoterapia;
use Database\Factories\ReglaAtrasoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Regla de la doctora para un tramo de días de atraso (HU-13).
 */
#[Table(name: 'reglas_atraso')]
#[Fillable(['fase', 'dias_desde', 'dias_hasta', 'accion', 'activo'])]
class ReglaAtraso extends Model
{
    /** @use HasFactory<ReglaAtrasoFactory> */
    use HasFactory;

    protected $attributes = [
        'activo' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fase' => FaseInmunoterapia::class,
            'activo' => 'boolean',
        ];
    }

    public static function paraAtraso(FaseInmunoterapia $fase, int $dias): ?self
    {
        return static::query()
            ->where('fase', $fase)
            ->where('activo', true)
            ->where('dias_desde', '<=', $dias)
            ->where(fn (Builder $query) => $query->whereNull('dias_hasta')->orWhere('dias_hasta', '>=', $dias))
            ->orderByDesc('dias_desde')
            ->first();
    }

    public function tramo(): string
    {
        return $this->dias_hasta === null
            ? "{$this->dias_desde} días o más"
            : "De {$this->dias_desde} a {$this->dias_hasta} días";
    }
}
