<?php

namespace Database\Seeders;

use App\Enums\GrupoAlergeno;
use App\Models\Alergeno;
use Illuminate\Database\Seeder;

/**
 * Panel inicial de prick test, basado en el panel habitual en México
 * (donde se formó la doctora). Ella lo ajusta desde Configuración.
 * Los alimentos quedan desactivados: suelen probarse en un panel aparte.
 */
class AlergenoSeeder extends Seeder
{
    public function run(): void
    {
        $panel = [
            ['Dermatophagoides pteronyssinus', 'Dermatophagoides pteronyssinus', GrupoAlergeno::Acaros, true],
            ['Dermatophagoides farinae', 'Dermatophagoides farinae', GrupoAlergeno::Acaros, true],
            ['Blomia tropicalis', 'Blomia tropicalis', GrupoAlergeno::Acaros, true],
            ['Epitelio de gato', 'Felis domesticus', GrupoAlergeno::Epitelios, true],
            ['Epitelio de perro', 'Canis familiaris', GrupoAlergeno::Epitelios, true],
            ['Cucaracha americana', 'Periplaneta americana', GrupoAlergeno::Insectos, true],
            ['Cucaracha alemana', 'Blattella germanica', GrupoAlergeno::Insectos, true],
            ['Alternaria', 'Alternaria alternata', GrupoAlergeno::Hongos, true],
            ['Aspergillus', 'Aspergillus fumigatus', GrupoAlergeno::Hongos, true],
            ['Cladosporium', 'Cladosporium herbarum', GrupoAlergeno::Hongos, true],
            ['Pasto Bermuda', 'Cynodon dactylon', GrupoAlergeno::Pastos, true],
            ['Ballico', 'Lolium perenne', GrupoAlergeno::Pastos, true],
            ['Fleo', 'Phleum pratense', GrupoAlergeno::Pastos, true],
            ['Fresno', 'Fraxinus americana', GrupoAlergeno::Arboles, true],
            ['Eucalipto', 'Eucalyptus globulus', GrupoAlergeno::Arboles, true],
            ['Ciprés', 'Cupressus arizonica', GrupoAlergeno::Arboles, true],
            ['Ambrosía', 'Ambrosia artemisiifolia', GrupoAlergeno::Malezas, true],
            ['Cenizo', 'Chenopodium album', GrupoAlergeno::Malezas, true],
            ['Amaranto', 'Amaranthus palmeri', GrupoAlergeno::Malezas, true],
            ['Llantén', 'Plantago lanceolata', GrupoAlergeno::Malezas, true],
            ['Leche de vaca', null, GrupoAlergeno::Alimentos, false],
            ['Huevo', null, GrupoAlergeno::Alimentos, false],
            ['Maní', 'Arachis hypogaea', GrupoAlergeno::Alimentos, false],
            ['Trigo', 'Triticum aestivum', GrupoAlergeno::Alimentos, false],
            ['Pescado', null, GrupoAlergeno::Alimentos, false],
            ['Mariscos', null, GrupoAlergeno::Alimentos, false],
        ];

        foreach ($panel as $orden => [$nombre, $cientifico, $grupo, $activo]) {
            Alergeno::query()->updateOrCreate(
                ['nombre' => $nombre],
                ['nombre_cientifico' => $cientifico, 'grupo' => $grupo, 'orden' => $orden + 1, 'activo' => $activo],
            );
        }
    }
}
