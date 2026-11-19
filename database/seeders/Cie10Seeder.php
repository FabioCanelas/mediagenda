<?php

namespace Database\Seeders;

use App\Models\Cie10;
use Illuminate\Database\Seeder;

/**
 * Diagnósticos CIE-10 frecuentes en alergología. El catálogo completo
 * se puede cargar más adelante; la doctora también puede escribir texto libre.
 */
class Cie10Seeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const DIAGNOSTICOS = [
        'H10.1' => 'Conjuntivitis atópica aguda',
        'H10.4' => 'Conjuntivitis crónica',
        'J30.0' => 'Rinitis vasomotora',
        'J30.1' => 'Rinitis alérgica debida al polen',
        'J30.2' => 'Otras rinitis alérgicas estacionales',
        'J30.3' => 'Otras rinitis alérgicas',
        'J30.4' => 'Rinitis alérgica, no especificada',
        'J31.0' => 'Rinitis crónica',
        'J32.9' => 'Sinusitis crónica, no especificada',
        'J45.0' => 'Asma predominantemente alérgica',
        'J45.1' => 'Asma no alérgica',
        'J45.8' => 'Asma mixta',
        'J45.9' => 'Asma, no especificada',
        'J46' => 'Estado asmático',
        'K52.2' => 'Gastroenteritis y colitis alérgicas y dietéticas',
        'L20.8' => 'Otras dermatitis atópicas',
        'L20.9' => 'Dermatitis atópica, no especificada',
        'L23.9' => 'Dermatitis alérgica de contacto, de causa no especificada',
        'L27.0' => 'Erupción generalizada de piel debida a drogas y medicamentos',
        'L27.2' => 'Dermatitis debida a ingestión de alimentos',
        'L50.0' => 'Urticaria alérgica',
        'L50.1' => 'Urticaria idiopática',
        'L50.8' => 'Otras urticarias',
        'L50.9' => 'Urticaria, no especificada',
        'T63.4' => 'Efecto tóxico del veneno de otros artrópodos',
        'T78.0' => 'Choque anafiláctico debido a reacción adversa a alimentos',
        'T78.1' => 'Otra reacción adversa a alimentos, no clasificada en otra parte',
        'T78.2' => 'Choque anafiláctico, no especificado',
        'T78.3' => 'Edema angioneurótico',
        'T78.4' => 'Alergia no especificada',
        'T88.6' => 'Choque anafiláctico debido a efecto adverso de droga o medicamento correcto administrado apropiadamente',
        'T88.7' => 'Efecto adverso no especificado de droga o medicamento',
        'Z88.0' => 'Historia personal de alergia a penicilina',
        'Z88.1' => 'Historia personal de alergia a otros agentes antibióticos',
        'Z91.0' => 'Historia personal de alergia, no debida a drogas ni a sustancias biológicas',
    ];

    public function run(): void
    {
        foreach (self::DIAGNOSTICOS as $codigo => $descripcion) {
            Cie10::query()->updateOrCreate(['codigo' => $codigo], ['descripcion' => $descripcion, 'activo' => true]);
        }
    }
}
