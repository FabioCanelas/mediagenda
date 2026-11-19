<?php

namespace Database\Factories;

use App\Enums\CategoriaAlergia;
use App\Enums\CriticidadAlergia;
use App\Enums\EstadoAlergia;
use App\Enums\GravedadReaccion;
use App\Models\Alergia;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alergia>
 */
class AlergiaFactory extends Factory
{
    /**
     * @var array<string, array<int, string>>
     */
    private const SUSTANCIAS = [
        'medicamento' => ['Penicilina', 'Amoxicilina', 'Ibuprofeno', 'Ácido acetilsalicílico', 'Metamizol'],
        'alimento' => ['Maní', 'Huevo', 'Leche de vaca', 'Mariscos', 'Pescado'],
        'ambiental' => ['Ácaros del polvo', 'Polen de gramíneas', 'Epitelio de gato', 'Látex'],
        'biologico' => ['Veneno de abeja', 'Veneno de avispa'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categoria = fake()->randomElement([CategoriaAlergia::Medicamento, CategoriaAlergia::Alimento, CategoriaAlergia::Ambiental, CategoriaAlergia::Biologico]);

        return [
            'paciente_id' => Paciente::factory(),
            'sustancia' => fake()->randomElement(self::SUSTANCIAS[$categoria->value]),
            'categoria' => $categoria,
            'estado' => fake()->randomElement([EstadoAlergia::Sospechada, EstadoAlergia::Confirmada]),
            'criticidad' => fake()->randomElement([CriticidadAlergia::Baja, CriticidadAlergia::Alta]),
            'gravedad' => fake()->randomElement(GravedadReaccion::cases()),
            'reaccion' => fake()->randomElement(['Urticaria generalizada', 'Edema de labios y párpados', 'Rinorrea y estornudos', 'Dificultad para respirar']),
        ];
    }

    public function confirmada(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => EstadoAlergia::Confirmada]);
    }

    public function descartada(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => EstadoAlergia::Descartada]);
    }
}
