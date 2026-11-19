<?php

namespace Tests\Feature;

use App\Filament\Resources\HistoriasClinicas\HistoriaClinicaResource;
use App\Filament\Resources\HistoriasClinicas\Pages\CreateHistoriaClinica;
use App\Models\Cie10;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * HU-03: historia clínica.
 */
class HistoriaClinicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->doctora()->create());
    }

    public function test_crea_la_historia_con_un_diagnostico_cie10_y_otro_en_texto_libre(): void
    {
        $paciente = Paciente::factory()->create();
        Cie10::factory()->create(['codigo' => 'J30.4', 'descripcion' => 'Rinitis alérgica, no especificada']);

        Livewire::withQueryParams(['paciente' => $paciente->id])
            ->test(CreateHistoriaClinica::class)
            ->fillForm([
                'padecimiento_actual' => 'Estornudos y rinorrea desde hace dos años.',
                'temperatura' => 36.5,
                'diagnosticos' => [
                    ['cie10_codigo' => 'J30.4', 'tipo' => 'principal', 'descripcion' => null],
                    ['cie10_codigo' => null, 'tipo' => 'secundario', 'descripcion' => 'Sensibilización a ácaros'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $historia = HistoriaClinica::firstWhere('paciente_id', $paciente->id);
        $this->assertSame(2, $historia->diagnosticos()->count());
        $this->assertSame(1, $historia->diagnosticos()->whereNull('cie10_codigo')->count());
    }

    public function test_un_diagnostico_sin_codigo_necesita_descripcion(): void
    {
        Livewire::withQueryParams(['paciente' => Paciente::factory()->create()->id])
            ->test(CreateHistoriaClinica::class)
            ->fillForm(['diagnosticos' => [['cie10_codigo' => null, 'tipo' => 'principal', 'descripcion' => null]]])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertDatabaseCount('historias_clinicas', 0);
    }

    public function test_rechaza_signos_vitales_fuera_de_rango(): void
    {
        Livewire::withQueryParams(['paciente' => Paciente::factory()->create()->id])
            ->test(CreateHistoriaClinica::class)
            ->fillForm(['temperatura' => 60])
            ->call('create')
            ->assertHasFormErrors(['temperatura' => 'max']);
    }

    public function test_si_el_paciente_ya_tiene_historia_abre_esa_en_vez_de_crear_otra(): void
    {
        $historia = HistoriaClinica::factory()->create();

        Livewire::withQueryParams(['paciente' => $historia->paciente_id])
            ->test(CreateHistoriaClinica::class)
            ->assertRedirect(HistoriaClinicaResource::getUrl('edit', ['record' => $historia]));
    }
}
