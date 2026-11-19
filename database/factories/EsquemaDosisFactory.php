<?php

namespace Database\Factories;

use App\Models\EsquemaDosis;
use App\Models\Inmunoterapia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EsquemaDosis>
 */
class EsquemaDosisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inmunoterapia_id' => Inmunoterapia::factory(),
            'paso' => 1,
            'dilucion' => '1:1000',
            'volumen_ml' => 0.05,
            'intervalo_dias' => 7,
        ];
    }
}
