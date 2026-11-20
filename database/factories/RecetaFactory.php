<?php

namespace Database\Factories;

use App\Models\Paciente;
use App\Models\Receta;
use App\Models\RecetaMedicamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receta>
 */
class RecetaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'fecha' => now()->toDateString(),
            'indicaciones' => 'Lavados nasales con solución salina dos veces al día. Control en 4 semanas.',
        ];
    }

    public function conMedicamentos(int $cantidad = 2): static
    {
        return $this->afterCreating(function (Receta $receta) use ($cantidad): void {
            foreach (range(1, $cantidad) as $orden) {
                RecetaMedicamento::factory()->for($receta)->numero($orden)->create(['orden' => $orden]);
            }
        });
    }
}
