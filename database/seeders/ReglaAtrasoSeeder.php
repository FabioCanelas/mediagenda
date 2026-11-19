<?php

namespace Database\Seeders;

use App\Models\ReglaAtraso;
use Illuminate\Database\Seeder;

/**
 * Reglas de ejemplo basadas en las indicaciones de la ACAAI para dosis atrasadas.
 * La ACAAI las expresa en días desde la última dosis; aquí están convertidas a días
 * de atraso (desde la fecha en que tocaba): en la fase de inicio, con dosis semanales,
 * 8 a 13 días desde la última equivalen a 1 a 6 días de atraso.
 *
 * Son un punto de partida: la doctora debe revisarlas en Configuración > Reglas de atraso.
 */
class ReglaAtrasoSeeder extends Seeder
{
    public function run(): void
    {
        $reglas = [
            ['inicio', 1, 6, 'Repetir la dosis anterior. (Ejemplo ACAAI, para revisar por la doctora)'],
            ['inicio', 7, 14, 'Reducir la dosis anterior un 25 %. (Ejemplo ACAAI, para revisar por la doctora)'],
            ['inicio', 15, 21, 'Reducir la dosis anterior un 50 %. (Ejemplo ACAAI, para revisar por la doctora)'],
            ['inicio', 22, null, 'Consultar con la doctora: puede requerir reiniciar el esquema. (Para revisar por la doctora)'],
            ['mantenimiento', 1, 14, 'Repetir la dosis de mantenimiento. (Ejemplo ACAAI, para revisar por la doctora)'],
            ['mantenimiento', 15, 28, 'Reducir la dosis de mantenimiento un 25 %. (Ejemplo ACAAI, para revisar por la doctora)'],
            ['mantenimiento', 29, null, 'Consultar con la doctora: volver a una dilución anterior. (Para revisar por la doctora)'],
        ];

        foreach ($reglas as [$fase, $desde, $hasta, $accion]) {
            ReglaAtraso::query()->updateOrCreate(
                ['fase' => $fase, 'dias_desde' => $desde],
                ['dias_hasta' => $hasta, 'accion' => $accion, 'activo' => true],
            );
        }
    }
}
