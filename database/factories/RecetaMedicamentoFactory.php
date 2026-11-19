<?php

namespace Database\Factories;

use App\Models\Receta;
use App\Models\RecetaMedicamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecetaMedicamento>
 */
class RecetaMedicamentoFactory extends Factory
{
    /**
     * @var array<int, array{string, string, string, string}>
     */
    private const MEDICAMENTOS = [
        ['Loratadina', '10 mg tabletas', '1 tableta cada 24 horas', 'por 30 días'],
        ['Mometasona furoato', 'spray nasal 50 mcg', '2 disparos en cada fosa nasal cada 24 horas', 'por 3 meses'],
        ['Montelukast', '10 mg tabletas', '1 tableta en la noche', 'por 30 días'],
        ['Salbutamol', 'inhalador 100 mcg', '2 inhalaciones si hay falta de aire', 'según necesidad'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$medicamento, $presentacion, $dosis, $duracion] = fake()->randomElement(self::MEDICAMENTOS);

        return [
            'receta_id' => Receta::factory(),
            'orden' => 1,
            'medicamento' => $medicamento,
            'presentacion' => $presentacion,
            'dosis' => $dosis,
            'duracion' => $duracion,
        ];
    }
}
