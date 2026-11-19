<?php

namespace Database\Seeders;

use App\Models\Alergeno;
use App\Models\Alergia;
use App\Models\Cie10;
use App\Models\Diagnostico;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Models\PruebaCutanea;
use App\Models\PruebaCutaneaResultado;
use Illuminate\Database\Seeder;

/**
 * 20 pacientes ficticios (3 menores de edad) con historia clínica,
 * diagnósticos, alergias y un prick test. Solo para desarrollo y demostración.
 */
class PacientesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $codigos = Cie10::query()->pluck('codigo');
        $panel = Alergeno::query()->delPanel()->get();

        Paciente::factory()->count(17)->create()
            ->concat(Paciente::factory()->menorDeEdad()->count(3)->create())
            ->each(function (Paciente $paciente) use ($codigos, $panel): void {
                $historia = HistoriaClinica::factory()->for($paciente)->create();

                Diagnostico::factory()->for($historia)->conCodigo($codigos->random())->create();

                if (fake()->boolean(40)) {
                    Diagnostico::factory()->for($historia)->create(['tipo' => 'secundario']);
                }

                Alergia::factory()->count(fake()->numberBetween(0, 3))->for($paciente)->create();

                $prueba = PruebaCutanea::factory()->for($paciente)->create();

                foreach ($panel as $alergeno) {
                    PruebaCutaneaResultado::factory()->for($prueba)->for($alergeno)->create();
                }
            });
    }
}
