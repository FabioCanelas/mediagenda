<?php

namespace Database\Factories;

use App\Models\Aplicacion;
use App\Models\Frasco;
use App\Models\Inmunoterapia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aplicacion>
 */
class AplicacionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inmunoterapia_id' => Inmunoterapia::factory(),
            'frasco_id' => fn (array $atributos) => Frasco::factory()->create(['inmunoterapia_id' => $atributos['inmunoterapia_id']])->id,
            'aplicada_en' => now()->subWeek(),
            'volumen_ml' => 0.05,
            'brazo' => fake()->randomElement(['izquierdo', 'derecho']),
            'reaccion_local' => 'ninguna',
        ];
    }
}
