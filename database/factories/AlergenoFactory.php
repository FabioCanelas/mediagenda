<?php

namespace Database\Factories;

use App\Enums\GrupoAlergeno;
use App\Models\Alergeno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alergeno>
 */
class AlergenoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Alérgeno '.fake()->unique()->word(),
            'grupo' => fake()->randomElement(GrupoAlergeno::cases()),
            'orden' => fake()->numberBetween(1, 100),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => ['activo' => false]);
    }
}
