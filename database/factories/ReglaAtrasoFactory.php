<?php

namespace Database\Factories;

use App\Models\ReglaAtraso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReglaAtraso>
 */
class ReglaAtrasoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fase' => 'inicio',
            'dias_desde' => 8,
            'dias_hasta' => 13,
            'accion' => 'Repetir la dosis anterior.',
            'activo' => true,
        ];
    }
}
