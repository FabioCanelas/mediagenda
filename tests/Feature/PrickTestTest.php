<?php

namespace Tests\Feature;

use App\Filament\Resources\PruebasCutaneas\Pages\CreatePruebaCutanea;
use App\Models\Alergeno;
use App\Models\Paciente;
use App\Models\PruebaCutanea;
use App\Models\PruebaCutaneaResultado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * HU-07: registro del prick test desde la pantalla.
 */
class PrickTestTest extends TestCase
{
    use RefreshDatabase;

    private User $doctora;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctora = User::factory()->doctora()->create();
        $this->actingAs($this->doctora);
    }

    public function test_el_formulario_trae_solo_los_alergenos_activos_del_panel(): void
    {
        $acaro = Alergeno::factory()->create(['nombre' => 'Ácaro', 'orden' => 1]);
        $gato = Alergeno::factory()->create(['nombre' => 'Gato', 'orden' => 2]);
        Alergeno::factory()->inactivo()->create(['nombre' => 'Maní']);

        $componente = Livewire::test(CreatePruebaCutanea::class);

        $alergenos = collect($componente->get('data.resultados'))->pluck('alergeno_id')->values()->all();
        $this->assertSame([$acaro->id, $gato->id], $alergenos);
    }

    public function test_guarda_la_prueba_y_calcula_los_positivos(): void
    {
        $paciente = Paciente::factory()->create();
        $acaro = Alergeno::factory()->create(['orden' => 1]);
        $gato = Alergeno::factory()->create(['orden' => 2]);

        Livewire::withQueryParams(['paciente' => $paciente->id])
            ->test(CreatePruebaCutanea::class)
            ->fillForm([
                'control_positivo_mm' => 5,
                'control_negativo_mm' => 0,
                'resultados' => [
                    ['alergeno_id' => $acaro->id, 'papula_mm' => 6],
                    ['alergeno_id' => $gato->id, 'papula_mm' => 1],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $prueba = PruebaCutanea::firstWhere('paciente_id', $paciente->id);
        $this->assertTrue($prueba->interpretable);
        $this->assertTrue($prueba->realizadoPor->is($this->doctora));
        $this->assertTrue($prueba->resultados()->where('alergeno_id', $acaro->id)->value('positivo'));
        $this->assertFalse($prueba->resultados()->where('alergeno_id', $gato->id)->value('positivo'));
    }

    public function test_exige_los_dos_controles(): void
    {
        Livewire::test(CreatePruebaCutanea::class)
            ->fillForm(['paciente_id' => Paciente::factory()->create()->id, 'control_positivo_mm' => null, 'control_negativo_mm' => null])
            ->call('create')
            ->assertHasFormErrors(['control_positivo_mm' => 'required', 'control_negativo_mm' => 'required']);

        $this->assertDatabaseCount('pruebas_cutaneas', 0);
    }

    public function test_si_cambian_los_controles_se_recalculan_los_resultados(): void
    {
        $prueba = PruebaCutanea::factory()->create(['control_positivo_mm' => 5, 'control_negativo_mm' => 0]);
        $resultado = PruebaCutaneaResultado::factory()->for($prueba)->create(['papula_mm' => 4]);
        $this->assertTrue($resultado->fresh()->positivo);

        $prueba->update(['control_negativo_mm' => 2]);

        $this->assertFalse($resultado->fresh()->positivo);
    }
}
