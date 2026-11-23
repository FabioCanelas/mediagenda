<?php

namespace Tests\Feature;

use App\Enums\TipoAdjunto;
use App\Filament\Resources\Pacientes\Pages\ViewPaciente;
use App\Filament\Resources\Pacientes\RelationManagers\AdjuntosRelationManager;
use App\Models\Adjunto;
use App\Models\Paciente;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * HU-08: estudios adjuntos al expediente.
 */
class AdjuntosTest extends TestCase
{
    use RefreshDatabase;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Adjunto::disco());
        $this->actingAs(User::factory()->doctora()->create());
        $this->paciente = Paciente::factory()->create();
    }

    public function test_adjunta_un_pdf_con_su_tipo_fecha_y_huella(): void
    {
        $contenido = "%PDF-1.4\n% Espirometria de prueba\n";

        $this->gestor()
            ->callAction(TestAction::make('create')->table(), data: [
                'tipo' => TipoAdjunto::Espirometria->value,
                'fecha_estudio' => now()->subDays(3)->toDateString(),
                'descripcion' => 'Espirometría con broncodilatador',
                'archivo' => UploadedFile::fake()->createWithContent('espirometria.pdf', $contenido),
            ])
            ->assertHasNoFormErrors();

        $adjunto = $this->paciente->adjuntos()->sole();

        $this->assertSame(TipoAdjunto::Espirometria, $adjunto->tipo);
        $this->assertSame('application/pdf', $adjunto->mime);
        $this->assertSame('espirometria.pdf', $adjunto->nombre_original);
        $this->assertSame(hash('sha256', $contenido), $adjunto->sha256);
        $this->assertStringStartsWith("adjuntos/{$this->paciente->getKey()}/", $adjunto->archivo);
        Storage::disk(Adjunto::disco())->assertExists($adjunto->archivo);
    }

    public function test_acepta_fotos_jpg_y_png(): void
    {
        $this->gestor()
            ->callAction(TestAction::make('create')->table(), data: [
                'tipo' => TipoAdjunto::Imagen->value,
                'fecha_estudio' => now()->toDateString(),
                'archivo' => UploadedFile::fake()->image('radiografia.png'),
            ])
            ->assertHasNoFormErrors();

        $this->assertTrue($this->paciente->adjuntos()->sole()->esImagen());
    }

    public function test_un_archivo_que_no_es_pdf_ni_imagen_se_rechaza(): void
    {
        $this->gestor()
            ->callAction(TestAction::make('create')->table(), data: [
                'tipo' => TipoAdjunto::Otro->value,
                'fecha_estudio' => now()->toDateString(),
                'archivo' => UploadedFile::fake()->createWithContent('programa.exe', "MZ\x90\x00binario"),
            ])
            ->assertHasFormErrors(['archivo']);

        $this->assertSame(0, Adjunto::query()->count());
    }

    public function test_descarga_el_archivo_original(): void
    {
        $adjunto = Adjunto::factory()->for($this->paciente)->create(['nombre_original' => 'laboratorio.pdf']);

        $this->gestor()
            ->callAction(TestAction::make('descargar')->table($adjunto))
            ->assertFileDownloaded('laboratorio.pdf');
    }

    public function test_si_el_archivo_cambio_en_el_disco_no_lo_entrega(): void
    {
        $adjunto = Adjunto::factory()->for($this->paciente)->create();
        Storage::disk(Adjunto::disco())->put($adjunto->archivo, 'contenido alterado');

        $this->assertFalse($adjunto->archivoIntegro());

        $this->gestor()
            ->callAction(TestAction::make('descargar')->table($adjunto))
            ->assertNotified('El archivo no coincide con el que se subió')
            ->assertNoFileDownloaded();
    }

    public function test_al_corregir_los_datos_no_se_cambia_el_archivo(): void
    {
        $adjunto = Adjunto::factory()->for($this->paciente)->create(['tipo' => TipoAdjunto::Otro]);
        $archivo = $adjunto->archivo;

        $this->gestor()
            ->callAction(TestAction::make('edit')->table($adjunto), data: ['tipo' => TipoAdjunto::Laboratorio->value])
            ->assertHasNoFormErrors();

        $adjunto->refresh();

        $this->assertSame(TipoAdjunto::Laboratorio, $adjunto->tipo);
        $this->assertSame($archivo, $adjunto->archivo);
    }

    public function test_la_recepcion_no_ve_los_estudios(): void
    {
        $this->actingAs(User::factory()->recepcion()->create());

        $this->assertFalse(AdjuntosRelationManager::canViewForRecord($this->paciente, ViewPaciente::class));
    }

    private function gestor(): Testable
    {
        return Livewire::test(AdjuntosRelationManager::class, [
            'ownerRecord' => $this->paciente,
            'pageClass' => ViewPaciente::class,
        ]);
    }
}
