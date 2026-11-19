<?php

namespace Database\Factories;

use App\Enums\TipoDiagnostico;
use App\Models\Diagnostico;
use App\Models\HistoriaClinica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Diagnostico>
 */
class DiagnosticoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'historia_clinica_id' => HistoriaClinica::factory(),
            'cie10_codigo' => null,
            'descripcion' => fake()->randomElement(['Rinitis alérgica persistente moderada', 'Asma alérgica leve intermitente', 'Urticaria crónica espontánea']),
            'tipo' => TipoDiagnostico::Principal,
        ];
    }

    public function conCodigo(string $codigo): static
    {
        return $this->state(fn (array $attributes) => [
            'cie10_codigo' => $codigo,
            'descripcion' => null,
        ]);
    }
}
