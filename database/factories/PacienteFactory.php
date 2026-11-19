<?php

namespace Database\Factories;

use App\Enums\Departamento;
use App\Enums\Sexo;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Pacientes ficticios. Nunca se usan datos reales.
 *
 * @extends Factory<Paciente>
 */
class PacienteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sexo = fake()->randomElement(Sexo::cases());

        return [
            'ci' => (string) fake()->unique()->numberBetween(1000000, 14999999),
            'ci_complemento' => '',
            'ci_expedido' => fake()->randomElement(Departamento::cases()),
            'nombres' => fake()->firstName($sexo === Sexo::Femenino ? 'female' : 'male'),
            'apellido_paterno' => fake()->lastName(),
            'apellido_materno' => fake()->lastName(),
            'fecha_nacimiento' => fake()->dateTimeBetween('-75 years', '-19 years')->format('Y-m-d'),
            'sexo' => $sexo,
            'celular' => fake()->randomElement(['6', '7']).fake()->numerify('#######'),
            'email' => fake()->optional(0.6)->safeEmail(),
            'direccion' => fake()->optional(0.8)->streetAddress(),
            'ocupacion' => fake()->randomElement(['Estudiante', 'Docente', 'Comerciante', 'Ingeniera', 'Contador', 'Enfermera', 'Chofer', 'Abogada', 'Agricultor', 'Ama de casa']),
            'contacto_emergencia_nombre' => fake()->name(),
            'contacto_emergencia_telefono' => '7'.fake()->numerify('#######'),
        ];
    }

    public function menorDeEdad(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_nacimiento' => fake()->dateTimeBetween('-17 years', '-4 years')->format('Y-m-d'),
            'ocupacion' => 'Estudiante',
            'tutor_nombre' => fake()->name(),
            'tutor_ci' => (string) fake()->numberBetween(1000000, 14999999),
            'tutor_parentesco' => fake()->randomElement(['Madre', 'Padre', 'Tutor legal']),
        ]);
    }
}
