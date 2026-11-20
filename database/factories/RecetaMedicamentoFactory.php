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
        return [
            'receta_id' => Receta::factory(),
            'orden' => 1,
            ...self::datosDel(fake()->randomElement(self::MEDICAMENTOS)),
        ];
    }

    /**
     * Elige el medicamento por número, para que una receta no repita el mismo.
     */
    public function numero(int $numero): static
    {
        return $this->state(fn (array $attributes) => self::datosDel(self::MEDICAMENTOS[$numero % count(self::MEDICAMENTOS)]));
    }

    /**
     * @param  array{string, string, string, string}  $medicamento
     * @return array<string, string>
     */
    private static function datosDel(array $medicamento): array
    {
        [$nombre, $presentacion, $dosis, $duracion] = $medicamento;

        return [
            'medicamento' => $nombre,
            'presentacion' => $presentacion,
            'dosis' => $dosis,
            'duracion' => $duracion,
        ];
    }
}
