<?php

namespace Database\Factories;

use App\Models\Cie10;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cie10>
 */
class Cie10Factory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('?##.#'),
            'descripcion' => fake()->sentence(4),
            'activo' => true,
        ];
    }
}
