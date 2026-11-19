<?php

namespace Database\Factories;

use App\Models\Paciente;
use App\Models\PruebaCutanea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Por defecto, una prueba interpretable: histamina 5 mm, solución salina 0 mm.
 *
 * @extends Factory<PruebaCutanea>
 */
class PruebaCutaneaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'fecha' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'control_positivo_mm' => 5.0,
            'control_negativo_mm' => 0.0,
            'criterio_positividad_mm' => PruebaCutanea::CRITERIO_POR_DEFECTO_MM,
            'minutos_lectura' => 15,
            'antihistaminicos_suspendidos' => true,
        ];
    }
}
