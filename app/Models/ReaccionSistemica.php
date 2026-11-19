<?php

namespace App\Models;

use App\Enums\GradoWao;
use App\Models\Concerns\RegistraAutor;
use Database\Factories\ReaccionSistemicaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reacción sistémica después de una aplicación, con el grado de la escala WAO (HU-12).
 */
#[Table(name: 'reacciones_sistemicas')]
#[Fillable(['aplicacion_id', 'grado', 'minutos_desde_aplicacion', 'sintomas', 'tratamiento', 'epinefrina'])]
class ReaccionSistemica extends Model
{
    /** @use HasFactory<ReaccionSistemicaFactory> */
    use HasFactory, HasUuids, RegistraAutor;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grado' => GradoWao::class,
            'epinefrina' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Aplicacion, $this>
     */
    public function aplicacion(): BelongsTo
    {
        return $this->belongsTo(Aplicacion::class);
    }

    /**
     * Texto corto para los avisos: el grado y la fecha de la aplicación que la provocó.
     */
    public function resumen(): string
    {
        return "{$this->grado->getLabel()} el {$this->aplicacion->aplicada_en->format('d/m/Y')}";
    }

    protected static function registraActualizador(): bool
    {
        return false;
    }
}
