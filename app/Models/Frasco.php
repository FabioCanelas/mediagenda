<?php

namespace App\Models;

use App\Enums\ColorFrasco;
use App\Enums\Dilucion;
use App\Models\Concerns\RegistraAutor;
use Carbon\CarbonInterface;
use Database\Factories\FrascoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Frasco de extracto (HU-10). El color se asigna solo según la dilución.
 */
#[Fillable(['inmunoterapia_id', 'dilucion', 'lote', 'fecha_preparacion', 'fecha_vencimiento', 'activo'])]
class Frasco extends Model
{
    /** @use HasFactory<FrascoFactory> */
    use HasFactory, HasUuids, RegistraAutor;

    protected $attributes = [
        'activo' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (Frasco $frasco): void {
            $frasco->color = $frasco->dilucion->color();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dilucion' => Dilucion::class,
            'color' => ColorFrasco::class,
            'fecha_preparacion' => 'date',
            'fecha_vencimiento' => 'date',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Inmunoterapia, $this>
     */
    public function inmunoterapia(): BelongsTo
    {
        return $this->belongsTo(Inmunoterapia::class);
    }

    public function estaVencido(?CarbonInterface $fecha = null): bool
    {
        return $this->fecha_vencimiento->lt(($fecha ?? now())->copy()->startOfDay());
    }

    public function sePuedeUsar(?CarbonInterface $fecha = null): bool
    {
        return $this->activo && ! $this->estaVencido($fecha);
    }

    /**
     * Frascos activos y sin vencer a la fecha indicada.
     *
     * @param  Builder<Frasco>  $query
     */
    #[Scope]
    protected function utilizables(Builder $query, ?CarbonInterface $fecha = null): void
    {
        $query->where('activo', true)->whereDate('fecha_vencimiento', '>=', ($fecha ?? now())->toDateString());
    }

    public function etiqueta(): string
    {
        return "{$this->dilucion->getLabel()} · lote {$this->lote} · vence {$this->fecha_vencimiento->format('d/m/Y')}";
    }

    protected static function registraActualizador(): bool
    {
        return false;
    }
}
