<?php

namespace Database\Factories;

use App\Models\Aplicacion;
use App\Models\ReaccionSistemica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReaccionSistemica>
 */
class ReaccionSistemicaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'aplicacion_id' => Aplicacion::factory(),
            'grado' => 1,
            'minutos_desde_aplicacion' => 15,
            'sintomas' => 'Prurito palmar y rinorrea.',
            'tratamiento' => 'Antihistamínico oral. Observación de 60 minutos.',
            'epinefrina' => false,
        ];
    }
}
