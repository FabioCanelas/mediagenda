<?php

namespace App\Models;

use Database\Factories\RecetaMedicamentoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'receta_medicamentos')]
#[Fillable(['receta_id', 'orden', 'medicamento', 'presentacion', 'dosis', 'duracion', 'indicaciones'])]
class RecetaMedicamento extends Model
{
    /** @use HasFactory<RecetaMedicamentoFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Receta, $this>
     */
    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }

    /**
     * Texto en una línea: "Loratadina 10 mg tabletas: 1 tableta cada 24 horas, por 30 días".
     */
    public function descripcion(): string
    {
        $nombre = trim("{$this->medicamento} {$this->presentacion}");
        $duracion = filled($this->duracion) ? ", {$this->duracion}" : '';

        return "{$nombre}: {$this->dosis}{$duracion}";
    }
}
