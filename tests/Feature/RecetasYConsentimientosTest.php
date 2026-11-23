<?php

namespace Tests\Feature;

use App\Enums\EstadoConsentimiento;
use App\Enums\ProcedimientoConsentimiento;
use App\Enums\TipoDocumento;
use App\Filament\Resources\Pacientes\Pages\ViewPaciente;
use App\Filament\Resources\Pacientes\RelationManagers\ConsentimientosRelationManager;
use App\Filament\Resources\Pacientes\RelationManagers\DocumentosEmitidosRelationManager;
use App\Filament\Resources\Pacientes\RelationManagers\RecetasRelationManager;
use App\Models\Alergia;
use App\Models\Consentimiento;
use App\Models\DocumentoEmitido;
use App\Models\Paciente;
use App\Models\Receta;
use App\Models\User;
use App\Support\Documentos\EmisorDeDocumentos;
use App\Support\Documentos\TextosConsentimiento;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

/**
 * HU-17 y HU-18: recetas y consentimientos en PDF con copia guardada.
 */
class RecetasYConsentimientosTest extends TestCase
{
    use RefreshDatabase;

    private User $doctora;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(DocumentoEmitido::disco());
        $this->travelTo(CarbonImmutable::parse('2026-11-23 10:00'));
        $this->doctora = User::factory()->doctora()->create();
        $this->actingAs($this->doctora);
        $this->paciente = Paciente::factory()->create(['nombres' => 'Lucía', 'apellido_paterno' => 'Quispe']);
    }

    public function test_crea_una_receta_con_varios_medicamentos(): void
    {
        $deshacer = Repeater::fake();

        $this->gestor(RecetasRelationManager::class)
            ->callAction(TestAction::make('create')->table(), data: [
                'fecha' => now()->toDateString(),
                'medicamentos' => [
                    ['medicamento' => 'Loratadina', 'presentacion' => '10 mg tabletas', 'dosis' => '1 tableta cada 24 horas', 'duracion' => 'por 30 días'],
                    ['medicamento' => 'Mometasona', 'presentacion' => 'spray nasal', 'dosis' => '2 disparos por fosa nasal en la mañana'],
                ],
                'indicaciones' => 'Lavados nasales con solución salina.',
            ])
            ->assertHasNoFormErrors();

        $deshacer();

        $receta = $this->paciente->recetas()->sole();

        $this->assertSame(['Loratadina', 'Mometasona'], $receta->medicamentos->pluck('medicamento')->all());
        $this->assertSame([1, 2], $receta->medicamentos->pluck('orden')->all());
        $this->assertFalse($receta->estaEmitida());
    }

    public function test_emitir_genera_el_pdf_guarda_la_copia_y_la_descarga(): void
    {
        $receta = Receta::factory()->for($this->paciente)->conMedicamentos()->create();

        $this->gestor(RecetasRelationManager::class)
            ->callAction(TestAction::make('emitir')->table($receta))
            ->assertNotified('Receta emitida con el folio R-2026-000001')
            ->assertFileDownloaded('R-2026-000001.pdf');

        $documento = $receta->documento()->sole();
        $contenido = Storage::disk(DocumentoEmitido::disco())->get($documento->archivo);

        $this->assertSame(TipoDocumento::Receta, $documento->tipo);
        $this->assertStringStartsWith('%PDF', $contenido);
        $this->assertSame(hash('sha256', $contenido), $documento->sha256);
        $this->assertSame($this->doctora->getKey(), $documento->emitido_por);
        $this->assertTrue($documento->archivoIntegro());
    }

    public function test_los_folios_son_correlativos_por_tipo(): void
    {
        $emisor = app(EmisorDeDocumentos::class);

        $primera = $emisor->emitirReceta(Receta::factory()->for($this->paciente)->conMedicamentos()->create());
        $segunda = $emisor->emitirReceta(Receta::factory()->for($this->paciente)->conMedicamentos()->create());
        $consentimiento = $emisor->emitirConsentimiento(Consentimiento::factory()->for($this->paciente)->create());

        $this->assertSame('R-2026-000001', $primera->folio);
        $this->assertSame('R-2026-000002', $segunda->folio);
        $this->assertSame('C-2026-000001', $consentimiento->folio);
    }

    public function test_una_receta_emitida_ya_no_se_edita_ni_se_vuelve_a_emitir(): void
    {
        $receta = Receta::factory()->for($this->paciente)->conMedicamentos()->create();
        app(EmisorDeDocumentos::class)->emitirReceta($receta);
        $receta->refresh();

        $this->assertFalse($this->doctora->can('update', $receta));
        $this->assertFalse($this->doctora->can('emitir', $receta));

        $this->gestor(RecetasRelationManager::class)
            ->assertActionHidden(TestAction::make('edit')->table($receta))
            ->assertActionHidden(TestAction::make('emitir')->table($receta))
            ->assertActionVisible(TestAction::make('descargarPdf')->table($receta));

        $this->expectException(LogicException::class);
        app(EmisorDeDocumentos::class)->emitirReceta($receta);
    }

    public function test_una_receta_sin_medicamentos_no_se_emite(): void
    {
        $receta = Receta::factory()->for($this->paciente)->create();

        $this->assertFalse($this->doctora->can('emitir', $receta));
    }

    public function test_un_documento_emitido_no_se_modifica_ni_se_borra(): void
    {
        $documento = app(EmisorDeDocumentos::class)->emitirReceta(Receta::factory()->for($this->paciente)->conMedicamentos()->create());

        try {
            $documento->update(['folio' => 'R-2026-999999']);
            $this->fail('Se pudo modificar el documento.');
        } catch (LogicException) {
            $this->assertSame('R-2026-000001', $documento->fresh()->folio);
        }

        $this->expectException(LogicException::class);
        $documento->delete();
    }

    public function test_la_reimpresion_avisa_si_la_copia_fue_alterada(): void
    {
        $documento = app(EmisorDeDocumentos::class)->emitirReceta(Receta::factory()->for($this->paciente)->conMedicamentos()->create());
        Storage::disk(DocumentoEmitido::disco())->put($documento->archivo, '%PDF-1.4 alterado');

        $this->gestor(DocumentosEmitidosRelationManager::class)
            ->callAction(TestAction::make('descargarPdf')->table($documento))
            ->assertNotified('La copia del documento no coincide con la emitida')
            ->assertNoFileDownloaded();
    }

    public function test_la_receta_impresa_muestra_las_alergias_del_paciente(): void
    {
        Alergia::factory()->for($this->paciente)->confirmada()->create(['sustancia' => 'Penicilina']);
        $receta = Receta::factory()->for($this->paciente)->conMedicamentos(1)->create();

        $html = view('pdf.receta', [
            'receta' => $receta->load('medicamentos'),
            'paciente' => $this->paciente->load('alergiasVigentes'),
            'folio' => 'R-2026-000001',
            'emitidoEn' => now(),
            'consultorio' => config('mediagenda.consultorio'),
        ])->render();

        $this->assertStringContainsString('Alergias: Penicilina', $html);
        $this->assertStringContainsString('Quispe', $html);
        $this->assertStringContainsString(config('mediagenda.consultorio.doctora'), $html);
    }

    public function test_genera_el_consentimiento_en_pdf_para_firmar(): void
    {
        $this->gestor(ConsentimientosRelationManager::class)
            ->callAction(TestAction::make('create')->table(), data: [
                'procedimiento' => ProcedimientoConsentimiento::Inmunoterapia->value,
            ])
            ->assertHasNoFormErrors();

        $consentimiento = $this->paciente->consentimientos()->sole();

        $this->assertSame(EstadoConsentimiento::Pendiente, $consentimiento->estado);
        $this->assertSame($this->paciente->nombre_completo, $consentimiento->firmante_nombre);
        $this->assertSame(config('mediagenda.consultorio.ciudad'), $consentimiento->lugar);
        $this->assertSame('C-2026-000001', $consentimiento->documento->folio);
        Storage::disk(DocumentoEmitido::disco())->assertExists($consentimiento->documento->archivo);
    }

    public function test_el_consentimiento_tiene_los_apartados_de_la_norma(): void
    {
        $consentimiento = Consentimiento::factory()->for($this->paciente)->prickTest()->create();

        $html = view('pdf.consentimiento', [
            'consentimiento' => $consentimiento,
            'textos' => TextosConsentimiento::para($consentimiento->procedimiento),
            'paciente' => $this->paciente,
            'folio' => 'C-2026-000001',
            'emitidoEn' => now(),
            'consultorio' => config('mediagenda.consultorio'),
        ])->render();

        foreach (['Lugar y fecha', 'En qué consiste', 'Beneficios esperados', 'Riesgos', 'contingencias y urgencias', 'Testigo 1', 'Testigo 2'] as $apartado) {
            $this->assertStringContainsString($apartado, $html);
        }
    }

    public function test_si_el_paciente_es_menor_firma_el_tutor(): void
    {
        $menor = Paciente::factory()->create([
            'fecha_nacimiento' => now()->subYears(10)->toDateString(),
            'tutor_nombre' => 'Rosa Mamani',
        ]);

        $valores = Consentimiento::valoresIniciales($menor);

        $this->assertSame('Rosa Mamani', $valores['firmante_nombre']);
        $this->assertTrue($valores['firmante_es_tutor']);
    }

    public function test_registrar_la_firma_cumple_el_recordatorio(): void
    {
        $consentimiento = app(EmisorDeDocumentos::class)->emitirConsentimiento(Consentimiento::factory()->for($this->paciente)->create())->consentimiento;

        $this->assertFalse($this->paciente->tieneConsentimientoFirmado(ProcedimientoConsentimiento::Inmunoterapia));

        $this->gestor(ConsentimientosRelationManager::class)
            ->callAction(TestAction::make('registrarFirma')->table($consentimiento))
            ->assertNotified('Firma registrada');

        $this->assertTrue($consentimiento->refresh()->estaFirmado());
        $this->assertNotNull($consentimiento->firmado_en);
        $this->assertTrue($this->paciente->tieneConsentimientoFirmado(ProcedimientoConsentimiento::Inmunoterapia));
        $this->assertFalse($this->paciente->tieneConsentimientoFirmado(ProcedimientoConsentimiento::PrickTest));

        $this->gestor(ConsentimientosRelationManager::class)
            ->assertActionHidden(TestAction::make('registrarFirma')->table($consentimiento));
    }

    public function test_la_recepcion_no_ve_recetas_ni_consentimientos(): void
    {
        $this->actingAs(User::factory()->recepcion()->create());

        foreach ([RecetasRelationManager::class, ConsentimientosRelationManager::class, DocumentosEmitidosRelationManager::class] as $gestor) {
            $this->assertFalse($gestor::canViewForRecord($this->paciente, ViewPaciente::class), $gestor);
        }
    }

    /**
     * @param  class-string  $gestor
     */
    private function gestor(string $gestor): Testable
    {
        return Livewire::test($gestor, [
            'ownerRecord' => $this->paciente,
            'pageClass' => ViewPaciente::class,
        ]);
    }
}
