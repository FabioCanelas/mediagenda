<?php

namespace Database\Factories;

use App\Models\Alergeno;
use App\Models\PruebaCutanea;
use App\Models\PruebaCutaneaResultado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PruebaCutaneaResultado>
 */
class PruebaCutaneaResultadoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prueba_cutanea_id' => PruebaCutanea::factory(),
            'alergeno_id' => Alergeno::factory(),
            // La mayoría de los alérgenos de un panel salen negativos.
            'papula_mm' => fake()->randomElement([0, 0, 0, 0, 0, 0, 0, 1, 1, 1.5, 2, 2, 3, 4, 6, 8]),
            'eritema_mm' => null,
        ];
    }
}
