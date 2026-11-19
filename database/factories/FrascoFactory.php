<?php

namespace Database\Factories;

use App\Models\Frasco;
use App\Models\Inmunoterapia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Frasco>
 */
class FrascoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inmunoterapia_id' => Inmunoterapia::factory(),
            'dilucion' => '1:1000',
            'lote' => strtoupper(fake()->bothify('L##??##')),
            'fecha_preparacion' => now()->subWeeks(2)->toDateString(),
            'fecha_vencimiento' => now()->addMonths(6)->toDateString(),
            'activo' => true,
        ];
    }

    public function vencido(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_preparacion' => now()->subYear()->toDateString(),
            'fecha_vencimiento' => now()->subDay()->toDateString(),
        ]);
    }
}
