<?php

namespace Database\Factories;

use App\Models\Inmunoterapia;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inmunoterapia>
 */
class InmunoterapiaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'via' => 'subcutanea',
            'extracto' => fake()->randomElement(['Mezcla de ácaros (D. pteronyssinus y D. farinae)', 'Ácaros y epitelio de gato', 'Mezcla de pólenes de pastos']),
            'fecha_inicio' => now()->subWeeks(4)->toDateString(),
            'fase' => 'inicio',
            'dosis_mantenimiento_ml' => 0.50,
            'intervalo_mantenimiento_dias' => 28,
            'estado' => 'activa',
        ];
    }

    public function mantenimiento(): static
    {
        return $this->state(fn (array $attributes) => ['fase' => 'mantenimiento']);
    }
}
