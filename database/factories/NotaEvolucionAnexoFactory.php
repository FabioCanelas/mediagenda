<?php

namespace Database\Factories;

use App\Models\NotaEvolucion;
use App\Models\NotaEvolucionAnexo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotaEvolucionAnexo>
 */
class NotaEvolucionAnexoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nota_evolucion_id' => NotaEvolucion::factory()->cerrada(),
            'texto' => 'Corrección: la frecuencia cardiaca registrada fue 78 lpm.',
        ];
    }
}
