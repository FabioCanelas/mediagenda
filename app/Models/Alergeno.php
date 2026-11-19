<?php

namespace App\Models;

use App\Enums\GrupoAlergeno;
use Database\Factories\AlergenoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Alérgeno del panel de prick test. La doctora decide cuáles están activos.
 */
#[Fillable(['nombre', 'nombre_cientifico', 'grupo', 'orden', 'activo'])]
class Alergeno extends Model
{
    /** @use HasFactory<AlergenoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grupo' => GrupoAlergeno::class,
            'activo' => 'boolean',
        ];
    }

    /**
     * Alérgenos activos del panel, en el orden en que se aplican.
     *
     * @param  Builder<Alergeno>  $query
     */
    #[Scope]
    protected function delPanel(Builder $query): void
    {
        $query->where('activo', true)->orderBy('orden')->orderBy('nombre');
    }
}
