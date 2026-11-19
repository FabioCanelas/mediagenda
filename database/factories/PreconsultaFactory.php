<?php

namespace Database\Factories;

use App\Enums\EstadoPreconsulta;
use App\Models\Paciente;
use App\Models\Preconsulta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Para obtener un enlace usable en las pruebas se usa Preconsulta::preparar(),
 * que devuelve el token en claro.
 *
 * @extends Factory<Preconsulta>
 */
class PreconsultaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'expira_en' => now()->addHours(3),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Preconsulta $preconsulta): void {
            $preconsulta->token_hash ??= Preconsulta::hashDelToken(Str::random(48));
        });
    }

    /**
     * Respuestas de un paciente con rinitis que ya envió el formulario.
     */
    public function enviada(): static
    {
        return $this->state(fn (array $attributes) => [
            'motivo_consulta' => 'Estornudos y nariz tapada todas las mañanas.',
            'tiempo_evolucion' => 'Hace 2 años',
            'sintomas' => ['nariz', 'ojos'],
            'antecedentes_familiares' => 'Madre con asma.',
            'alergia_medicamentos' => 'si',
            'alergia_medicamentos_detalle' => 'Penicilina',
            'medicamentos_actuales' => 'Loratadina cuando tiene síntomas.',
            'mascotas' => 'Un gato',
            'fuma' => false,
            'celular' => '71234567',
        ])->afterMaking(function (Preconsulta $preconsulta): void {
            $preconsulta->estado = EstadoPreconsulta::Enviada;
            $preconsulta->enviada_en = now();
        });
    }
}
