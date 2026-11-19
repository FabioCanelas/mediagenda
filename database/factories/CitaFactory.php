<?php

namespace Database\Factories;

use App\Models\Cita;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cita>
 */
class CitaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = now()->addDays(fake()->numberBetween(1, 10))->setTime(fake()->numberBetween(8, 17), 0);

        return [
            'paciente_id' => Paciente::factory(),
            'inicio' => $inicio,
            'fin' => $inicio->copy()->addMinutes(30),
            'tipo' => 'control',
            'estado' => 'programada',
        ];
    }
}
