<?php

namespace Database\Factories;

use App\Models\CuestionarioAct;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CuestionarioAct>
 */
class CuestionarioActFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'fecha' => fake()->dateTimeBetween('-6 months')->format('Y-m-d'),
            'respuesta_1' => fake()->numberBetween(2, 5),
            'respuesta_2' => fake()->numberBetween(2, 5),
            'respuesta_3' => fake()->numberBetween(2, 5),
            'respuesta_4' => fake()->numberBetween(2, 5),
            'respuesta_5' => fake()->numberBetween(2, 5),
        ];
    }

    /**
     * Respuestas que suman exactamente el puntaje indicado (de 5 a 25).
     */
    public function conPuntaje(int $puntaje): static
    {
        $base = intdiv($puntaje, 5);
        $resto = $puntaje % 5;

        return $this->state(fn (array $attributes) => collect(range(1, 5))
            ->mapWithKeys(fn (int $pregunta): array => ["respuesta_{$pregunta}" => $base + ($pregunta <= $resto ? 1 : 0)])
            ->all());
    }
}
