<?php

namespace Tests\Feature;

use App\Enums\EstadoAlergia;
use App\Filament\Resources\Pacientes\Pages\ViewPaciente;
use App\Filament\Resources\Pacientes\RelationManagers\AlergiasRelationManager;
use App\Filament\Widgets\AvisoAlergias;
use App\Models\Alergia;
use App\Models\Paciente;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * HU-04: alergias del paciente y aviso rojo.
 */
class AlergiasYAvisoTest extends TestCase
{
    use RefreshDatabase;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->doctora()->create());
        $this->paciente = Paciente::factory()->create();
    }

    public function test_el_aviso_muestra_las_alergias_vigentes_y_oculta_las_descartadas(): void
    {
        Alergia::factory()->for($this->paciente)->confirmada()->create(['sustancia' => 'Penicilina']);
        Alergia::factory()->for($this->paciente)->descartada()->create(['sustancia' => 'Látex']);

        Livewire::test(AvisoAlergias::class, ['record' => $this->paciente])
            ->assertSee('Penicilina')
            ->assertDontSee('Látex');
    }

    public function test_sin_alergias_el_aviso_lo_dice(): void
    {
        Livewire::test(AvisoAlergias::class, ['record' => $this->paciente])
            ->assertSee('Sin alergias registradas');
    }

    public function test_registra_una_alergia_desde_la_ficha_del_paciente(): void
    {
        Livewire::test(AlergiasRelationManager::class, ['ownerRecord' => $this->paciente, 'pageClass' => ViewPaciente::class])
            ->callAction(TestAction::make('create')->table(), data: [
                'sustancia' => 'Penicilina',
                'categoria' => 'medicamento',
                'tipo' => 'alergia',
                'estado' => 'confirmada',
                'criticidad' => 'alta',
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('alergias', ['paciente_id' => $this->paciente->id, 'sustancia' => 'Penicilina', 'estado' => 'confirmada']);
    }

    public function test_descartar_una_alergia_la_conserva_con_estado_descartada(): void
    {
        $alergia = Alergia::factory()->for($this->paciente)->confirmada()->create();

        Livewire::test(AlergiasRelationManager::class, ['ownerRecord' => $this->paciente, 'pageClass' => ViewPaciente::class])
            ->callAction(TestAction::make('descartar')->table($alergia));

        $this->assertSame(EstadoAlergia::Descartada, $alergia->fresh()->estado);
        $this->assertNull($alergia->fresh()->deleted_at);
    }
}
