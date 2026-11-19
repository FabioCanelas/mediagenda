<?php

namespace App\Models;

use App\Enums\ControlAsma;
use App\Models\Concerns\RegistraAutor;
use Database\Factories\CuestionarioActFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuestionario de control del asma (HU-16): 5 preguntas de 1 a 5 puntos sobre
 * las últimas 4 semanas. La base calcula el puntaje (columna generada).
 *
 * Las preguntas están resumidas: la doctora usa el formulario oficial del ACT,
 * que tiene licencia, y aquí solo anota las respuestas.
 */
#[Table(name: 'cuestionarios_act')]
#[Fillable(['paciente_id', 'fecha', 'respuesta_1', 'respuesta_2', 'respuesta_3', 'respuesta_4', 'respuesta_5', 'observaciones'])]
class CuestionarioAct extends Model
{
    /** @use HasFactory<CuestionarioActFactory> */
    use HasFactory, HasUuids, RegistraAutor;

    /**
     * Pregunta => opciones de 1 a 5 puntos.
     *
     * @var array<int, array{titulo: string, pregunta: string, opciones: array<int, string>}>
     */
    public const PREGUNTAS = [
        1 => [
            'titulo' => 'Actividades',
            'pregunta' => '¿Cuánto le impidió el asma hacer sus actividades en el trabajo, el estudio o la casa?',
            'opciones' => [1 => 'Siempre', 2 => 'Casi siempre', 3 => 'A veces', 4 => 'Pocas veces', 5 => 'Nunca'],
        ],
        2 => [
            'titulo' => 'Falta de aire',
            'pregunta' => '¿Con qué frecuencia le faltó el aire?',
            'opciones' => [1 => 'Más de una vez al día', 2 => 'Una vez al día', 3 => 'De 3 a 6 veces por semana', 4 => '1 o 2 veces por semana', 5 => 'Nunca'],
        ],
        3 => [
            'titulo' => 'Síntomas de noche',
            'pregunta' => '¿Con qué frecuencia los síntomas lo despertaron de noche o más temprano que de costumbre?',
            'opciones' => [1 => '4 noches o más por semana', 2 => '2 o 3 noches por semana', 3 => 'Una vez por semana', 4 => '1 o 2 veces en el mes', 5 => 'Nunca'],
        ],
        4 => [
            'titulo' => 'Medicamento de rescate',
            'pregunta' => '¿Con qué frecuencia usó el inhalador de rescate?',
            'opciones' => [1 => '3 o más veces al día', 2 => '1 o 2 veces al día', 3 => '2 o 3 veces por semana', 4 => 'Una vez por semana o menos', 5 => 'Nunca'],
        ],
        5 => [
            'titulo' => 'Control percibido',
            'pregunta' => '¿Cómo califica el control de su asma?',
            'opciones' => [1 => 'Nada controlada', 2 => 'Mal controlada', 3 => 'Algo controlada', 4 => 'Bien controlada', 5 => 'Totalmente controlada'],
        ],
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'respuesta_1' => 'integer',
            'respuesta_2' => 'integer',
            'respuesta_3' => 'integer',
            'respuesta_4' => 'integer',
            'respuesta_5' => 'integer',
            'puntaje' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    /**
     * Suma de las respuestas, de 5 a 25. Coincide con la columna `puntaje` de la base.
     */
    public function puntajeTotal(): int
    {
        return array_sum(array_map(fn (int $pregunta): int => (int) $this->getAttribute("respuesta_{$pregunta}"), array_keys(self::PREGUNTAS)));
    }

    public function control(): ControlAsma
    {
        return ControlAsma::segunPuntaje($this->puntajeTotal());
    }
}
