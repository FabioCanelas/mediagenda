<?php

namespace Database\Seeders;

use App\Enums\EstadoCita;
use App\Enums\ProcedimientoConsentimiento;
use App\Enums\TipoAdjunto;
use App\Models\Adjunto;
use App\Models\Cita;
use App\Models\Consentimiento;
use App\Models\CuestionarioAct;
use App\Models\Inmunoterapia;
use App\Models\Paciente;
use App\Models\Preconsulta;
use App\Models\Receta;
use App\Models\RecetaMedicamento;
use App\Models\User;
use App\Support\Documentos\EmisorDeDocumentos;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Datos ficticios del Sprint 3: cuestionarios ACT, consentimientos (uno falta
 * a propósito para ver el recordatorio), recetas, estudios y una pre-consulta
 * enviada desde la tablet que espera la revisión de la doctora.
 */
class DocumentosDemoSeeder extends Seeder
{
    public function run(EmisorDeDocumentos $emisor): void
    {
        $doctora = User::query()->where('rol', 'doctora')->first();

        if ($doctora !== null) {
            Auth::setUser($doctora);
        }

        $pacientes = Paciente::query()->orderBy('apellido_paterno')->get();

        $this->cuestionariosAct($pacientes->slice(6, 2)->values());
        $this->consentimientos($emisor);
        $this->recetas($pacientes->take(3), $emisor);
        $this->estudios($pacientes->take(2));
        $this->preconsulta($pacientes);
    }

    /**
     * Un paciente que mejora con el tratamiento y otro que empeora.
     *
     * @param  iterable<int, Paciente>  $pacientes
     */
    private function cuestionariosAct(iterable $pacientes): void
    {
        $evoluciones = [[11, 14, 18, 21], [20, 18, 15, 13]];

        foreach ($pacientes as $indice => $paciente) {
            foreach ($evoluciones[$indice] as $mesesAtras => $puntaje) {
                CuestionarioAct::factory()->for($paciente)->conPuntaje($puntaje)->create([
                    'fecha' => now()->subMonths(6 - $mesesAtras * 2)->toDateString(),
                ]);
            }
        }
    }

    /**
     * Consentimiento firmado para cada inmunoterapia, menos la que empieza hoy.
     */
    private function consentimientos(EmisorDeDocumentos $emisor): void
    {
        Inmunoterapia::query()->with('paciente')->withCount('aplicaciones')->get()
            ->each(function (Inmunoterapia $inmunoterapia) use ($emisor): void {
                if ($inmunoterapia->aplicaciones_count === 0) {
                    return;
                }

                $consentimiento = $inmunoterapia->paciente->consentimientos()->create([
                    ...Consentimiento::valoresIniciales($inmunoterapia->paciente),
                    'procedimiento' => ProcedimientoConsentimiento::Inmunoterapia,
                    'fecha' => $inmunoterapia->fecha_inicio->toDateString(),
                ]);

                $emisor->emitirConsentimiento($consentimiento);
                $consentimiento->registrarFirma();
            });
    }

    /**
     * Una receta emitida por paciente y un borrador sin emitir.
     *
     * @param  iterable<int, Paciente>  $pacientes
     */
    private function recetas(iterable $pacientes, EmisorDeDocumentos $emisor): void
    {
        foreach ($pacientes as $indice => $paciente) {
            $receta = Receta::factory()->for($paciente)->create(['fecha' => now()->subDays(10 - $indice)->toDateString()]);

            foreach (range(1, 2) as $orden) {
                RecetaMedicamento::factory()->for($receta)->numero($indice + $orden)->create(['orden' => $orden]);
            }

            if ($indice < 2) {
                $emisor->emitirReceta($receta);
            }
        }
    }

    /**
     * Estudios en PDF generados al vuelo, marcados como ficticios.
     *
     * @param  iterable<int, Paciente>  $pacientes
     */
    private function estudios(iterable $pacientes): void
    {
        $estudios = [
            [TipoAdjunto::Laboratorio, 'IgE total y eosinófilos', 'IgE total: 420 UI/mL (ref. < 100). Eosinófilos: 8 %.'],
            [TipoAdjunto::Espirometria, 'Espirometría con broncodilatador', 'VEF1 78 % del predicho; mejora del 14 % tras broncodilatador.'],
        ];

        foreach ($pacientes as $indice => $paciente) {
            [$tipo, $descripcion, $resultado] = $estudios[$indice];
            $ruta = "adjuntos/{$paciente->getKey()}/".Str::uuid().'.pdf';

            Storage::disk(Adjunto::DISCO)->put($ruta, Pdf::loadHTML(
                "<h2>{$descripcion}</h2><p>Paciente: {$paciente->nombre_completo}</p><p>{$resultado}</p><p><em>Estudio ficticio de demostración.</em></p>",
            )->output());

            $paciente->adjuntos()->create([
                'tipo' => $tipo,
                'fecha_estudio' => now()->subWeeks(2)->toDateString(),
                'descripcion' => $descripcion,
                'archivo' => $ruta,
                'nombre_original' => Str::slug($descripcion).'.pdf',
            ]);
        }
    }

    /**
     * El paciente de la primera consulta de hoy ya llenó la pre-consulta.
     *
     * @param  iterable<int, Paciente>  $pacientes
     */
    private function preconsulta(iterable $pacientes): void
    {
        $cita = Cita::query()
            ->delDia(now())
            ->where('tipo', 'primera_consulta')
            ->whereIn('estado', [EstadoCita::Programada, EstadoCita::Confirmada])
            ->first();

        $paciente = $cita?->paciente ?? collect($pacientes)->last();

        Preconsulta::factory()->for($paciente)->enviada()->create([
            'cita_id' => $cita?->getKey(),
            'motivo_consulta' => 'Tos seca en las noches y silbido en el pecho cuando hace frío.',
            'tiempo_evolucion' => 'Hace 8 meses',
            'sintomas' => ['pecho', 'nariz'],
            'antecedentes_familiares' => 'Padre con rinitis alérgica.',
            'alergia_medicamentos' => 'no',
            'alergia_medicamentos_detalle' => null,
            'mascotas' => 'Un perro',
            'celular' => $paciente->celular,
        ]);
    }
}
