<?php

namespace Database\Factories;

use App\Models\NotaEvolucion;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotaEvolucion>
 */
class NotaEvolucionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'fecha' => fake()->dateTimeBetween('-6 months')->format('Y-m-d'),
            'evolucion' => fake()->randomElement([
                'Refiere mejoría de los síntomas nasales con el tratamiento. Persisten estornudos matutinos ocasionales.',
                'Asma controlada, sin despertares nocturnos ni uso de rescate en el último mes.',
                'Disminución de las ronchas desde el inicio del antihistamínico. Sin angioedema.',
            ]),
            'frecuencia_cardiaca' => fake()->numberBetween(60, 95),
            'saturacion_oxigeno' => fake()->numberBetween(92, 98),
            'diagnosticos' => 'Rinitis alérgica persistente moderada.',
            'pronostico' => 'Bueno con tratamiento y control ambiental.',
            'tratamiento' => 'Continuar antihistamínico oral cada 24 horas. Control en 4 semanas.',
        ];
    }

    public function cerrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'cerrada',
            'cerrada_en' => now(),
            'cerrada_por' => User::factory()->doctora(),
        ]);
    }
}
