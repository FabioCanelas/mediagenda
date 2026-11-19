<?php

namespace Database\Seeders;

use App\Enums\Dilucion;
use App\Enums\EstadoCita;
use App\Enums\TipoCita;
use App\Models\Alergeno;
use App\Models\Aplicacion;
use App\Models\Cita;
use App\Models\Frasco;
use App\Models\Inmunoterapia;
use App\Models\NotaEvolucion;
use App\Models\Paciente;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Datos ficticios del Sprint 2: inmunoterapias en distintos momentos del
 * tratamiento (al día, para hoy, atrasadas, en mantenimiento), notas de
 * evolución y citas de esta semana y la siguiente, sin cruces de horario.
 */
class ConsultorioDemoSeeder extends Seeder
{
    public function run(): void
    {
        $pacientes = Paciente::query()->orderBy('apellido_paterno')->get();
        $doctora = User::query()->where('rol', 'doctora')->first();
        $alergenos = Alergeno::query()->delPanel()->limit(6)->get();

        $this->inmunoterapias($pacientes->take(6), $alergenos);
        $this->notas($pacientes->take(10), $doctora);
        $this->citas($pacientes);
    }

    /**
     * Cada paciente queda en una situación distinta según cuántos días pasaron desde su última dosis.
     *
     * @param  Collection<int, Paciente>  $pacientes
     * @param  Collection<int, Alergeno>  $alergenos
     */
    private function inmunoterapias(Collection $pacientes, Collection $alergenos): void
    {
        // [dosis aplicadas, días desde la última, fase]
        $situaciones = [
            [3, 7, 'inicio'],          // le toca hoy
            [5, 20, 'inicio'],         // atrasada 13 días
            [2, 3, 'inicio'],          // al día
            [0, 0, 'inicio'],          // empieza hoy
            [1, 35, 'mantenimiento'],  // atrasada 7 días en mantenimiento
            [4, 30, 'inicio'],         // atrasada 23 días
        ];

        foreach ($pacientes->values() as $indice => $paciente) {
            [$aplicadas, $diasDesdeUltima, $fase] = $situaciones[$indice];
            $ultima = CarbonImmutable::now()->subDays($diasDesdeUltima)->setTime(10, 0);

            $inmunoterapia = Inmunoterapia::factory()->for($paciente)->create([
                'fase' => $fase,
                'fecha_inicio' => $aplicadas === 0 ? now()->toDateString() : $ultima->subWeeks($aplicadas)->toDateString(),
            ]);
            $inmunoterapia->alergenos()->attach($alergenos->random(2)->pluck('id'));
            $inmunoterapia->esquema()->createMany(Inmunoterapia::esquemaConvencional());

            $frascos = collect(Dilucion::cases())
                ->reject(fn (Dilucion $dilucion): bool => $dilucion === Dilucion::DiezMil)
                ->mapWithKeys(fn (Dilucion $dilucion): array => [$dilucion->value => Frasco::factory()->for($inmunoterapia)->create([
                    'dilucion' => $dilucion,
                    'fecha_preparacion' => $ultima->subWeeks(6)->toDateString(),
                ])]);

            $pasos = $inmunoterapia->esquema()->get();

            for ($n = 0; $n < $aplicadas; $n++) {
                $fecha = $ultima->subWeeks($aplicadas - 1 - $n);
                $paso = $fase === 'inicio' ? $pasos[$n] : null;
                $frasco = $frascos[$paso?->dilucion->value ?? Dilucion::Concentrado->value];

                $aplicacion = Aplicacion::query()->create([
                    'inmunoterapia_id' => $inmunoterapia->id,
                    'frasco_id' => $frasco->id,
                    'esquema_dosis_id' => $paso?->id,
                    'aplicada_en' => $fecha,
                    'volumen_ml' => $paso?->volumen_ml ?? $inmunoterapia->dosis_mantenimiento_ml,
                    'brazo' => $n % 2 === 0 ? 'izquierdo' : 'derecho',
                    'reaccion_local' => $n === 2 ? 'leve' : 'ninguna',
                ]);
                $aplicacion->forceFill(['observacion_completada_en' => $fecha->addMinutes(Aplicacion::MINUTOS_OBSERVACION)])->save();

                if ($indice === 1 && $n === $aplicadas - 1) {
                    $aplicacion->reaccionSistemica()->create([
                        'grado' => 1,
                        'minutos_desde_aplicacion' => 20,
                        'sintomas' => 'Prurito palmar y rinorrea.',
                        'tratamiento' => 'Antihistamínico oral y observación de 60 minutos.',
                        'epinefrina' => false,
                    ]);
                }
            }
        }
    }

    /**
     * @param  Collection<int, Paciente>  $pacientes
     */
    private function notas(Collection $pacientes, ?User $doctora): void
    {
        foreach ($pacientes as $indice => $paciente) {
            $nota = NotaEvolucion::factory()->for($paciente)->create();

            if ($doctora !== null && $indice % 3 !== 2) {
                $nota->cerrar($doctora);
            }

            if ($indice === 0 && $nota->estaCerrada()) {
                $nota->anexos()->create(['texto' => 'Corrección: el paciente usa el antihistamínico cada 24 horas, no cada 12.']);
            }
        }
    }

    /**
     * Citas de lunes a sábado de esta semana y la siguiente, una detrás de otra.
     *
     * @param  Collection<int, Paciente>  $pacientes
     */
    private function citas(Collection $pacientes): void
    {
        $lunes = CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY);
        $tipos = TipoCita::cases();
        $indicePaciente = 0;

        foreach (range(0, 11) as $dia) {
            $fecha = $lunes->addDays($dia + intdiv($dia, 6));
            $hora = $fecha->setTime(8, 30);

            foreach (range(1, 3) as $n) {
                $tipo = $tipos[($dia + $n) % count($tipos)];
                $fin = $hora->addMinutes($tipo->duracionMinutos());

                Cita::query()->create([
                    'paciente_id' => $pacientes[$indicePaciente++ % $pacientes->count()]->id,
                    'inicio' => $hora,
                    'fin' => $fin,
                    'tipo' => $tipo,
                    'estado' => $fin->isPast() ? EstadoCita::Atendida : ($n === 1 ? EstadoCita::Confirmada : EstadoCita::Programada),
                ]);

                $hora = $fin->addMinutes(30);
            }
        }
    }
}
