<?php

namespace Database\Factories;

use App\Enums\TipoAdjunto;
use App\Models\Adjunto;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends Factory<Adjunto>
 */
class AdjuntoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'tipo' => fake()->randomElement([TipoAdjunto::Laboratorio, TipoAdjunto::Espirometria]),
            'fecha_estudio' => fake()->dateTimeBetween('-3 months')->format('Y-m-d'),
            'descripcion' => fake()->randomElement(['Hemograma e IgE total', 'Espirometría con broncodilatador', 'IgE específica a ácaros']),
            'archivo' => function (): string {
                $ruta = 'adjuntos/demo/'.Str::uuid().'.pdf';
                Storage::disk(Adjunto::DISCO)->put($ruta, "%PDF-1.4\n% Estudio ficticio de demostracion\n");

                return $ruta;
            },
            'nombre_original' => 'estudio.pdf',
        ];
    }
}
