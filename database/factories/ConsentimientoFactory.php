<?php

namespace Database\Factories;

use App\Enums\ProcedimientoConsentimiento;
use App\Models\Consentimiento;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consentimiento>
 */
class ConsentimientoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'procedimiento' => ProcedimientoConsentimiento::Inmunoterapia,
            'fecha' => now()->toDateString(),
            'lugar' => 'La Paz, Bolivia',
            'firmante_nombre' => fake()->name(),
            'firmante_es_tutor' => false,
        ];
    }

    public function prickTest(): static
    {
        return $this->state(fn (array $attributes) => ['procedimiento' => ProcedimientoConsentimiento::PrickTest]);
    }

    public function firmado(): static
    {
        return $this->afterCreating(fn (Consentimiento $consentimiento) => $consentimiento->registrarFirma());
    }
}
