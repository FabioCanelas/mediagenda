<?php

namespace App\Models;

use Database\Factories\Cie10Factory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de diagnósticos CIE-10 (solo lectura para la aplicación).
 */
#[Table(name: 'cie10', key: 'codigo', keyType: 'string', incrementing: false, timestamps: false)]
#[Fillable(['codigo', 'descripcion', 'activo'])]
class Cie10 extends Model
{
    /** @use HasFactory<Cie10Factory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function etiqueta(): Attribute
    {
        return Attribute::get(fn (): string => "{$this->codigo} · {$this->descripcion}");
    }
}
