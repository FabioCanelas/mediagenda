<?php

namespace Tests\Feature;

use App\Enums\ControlAsma;
use App\Filament\Resources\Pacientes\Pages\ViewPaciente;
use App\Filament\Resources\Pacientes\RelationManagers\CuestionariosActRelationManager;
use App\Filament\Widgets\EvolucionAct;
use App\Models\CuestionarioAct;
use App\Models\Paciente;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * HU-16: cuestionario de control del asma (ACT).
 */
class CuestionarioActTest extends TestCase
{
    use RefreshDatabase;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->doctora()->create());
        $this->paciente = Paciente::factory()->create();
    }

    public function test_registra_las_5_respuestas_y_la_base_calcula_el_puntaje(): void
    {
        $this->gestor()
            ->callAction(TestAction::make('create')->table(), data: [
                'fecha' => now()->toDateString(),
                'respuesta_1' => 4,
                'respuesta_2' => 3,
                'respuesta_3' => 4,
                'respuesta_4' => 3,
                'respuesta_5' => 3,
            ])
            ->assertHasNoFormErrors();

        $cuestionario = $this->paciente->cuestionariosAct()->sole();

        $this->assertSame(17, $cuestionario->puntaje);
        $this->assertSame(17, $cuestionario->puntajeTotal());
        $this->assertSame(ControlAsma::ParcialmenteControlada, $cuestionario->control());
    }

    public function test_las_5_preguntas_son_obligatorias_y_van_de_1_a_5(): void
    {
        $this->gestor()
            ->callAction(TestAction::make('create')->table(), data: [
                'fecha' => now()->toDateString(),
                'respuesta_1' => 4,
                'respuesta_2' => 9,
            ])
            ->assertHasFormErrors(['respuesta_2' => 'in', 'respuesta_3' => 'required', 'respuesta_5' => 'required']);

        $this->assertSame(0, CuestionarioAct::query()->count());
    }

    /**
     * @return array<string, array{int, ControlAsma}>
     */
    public static function interpretaciones(): array
    {
        return [
            'máximo' => [25, ControlAsma::Controlada],
            'justo controlada' => [20, ControlAsma::Controlada],
            'parcial alta' => [19, ControlAsma::ParcialmenteControlada],
            'parcial baja' => [16, ControlAsma::ParcialmenteControlada],
            'no controlada' => [15, ControlAsma::NoControlada],
            'mínimo' => [5, ControlAsma::NoControlada],
        ];
    }

    #[DataProvider('interpretaciones')]
    public function test_interpreta_el_puntaje(int $puntaje, ControlAsma $control): void
    {
        $cuestionario = CuestionarioAct::factory()->for($this->paciente)->conPuntaje($puntaje)->create();

        $this->assertSame($puntaje, $cuestionario->refresh()->puntaje);
        $this->assertSame($control, $cuestionario->control());
    }

    public function test_el_grafico_muestra_el_puntaje_en_orden_de_fecha(): void
    {
        CuestionarioAct::factory()->for($this->paciente)->conPuntaje(21)->create(['fecha' => '2026-10-01']);
        CuestionarioAct::factory()->for($this->paciente)->conPuntaje(12)->create(['fecha' => '2026-06-01']);
        CuestionarioAct::factory()->for($this->paciente)->conPuntaje(17)->create(['fecha' => '2026-08-01']);

        $widget = new EvolucionAct;
        $widget->record = $this->paciente;
        $datos = (fn (): array => $this->getData())->call($widget);

        $this->assertSame(['01/06/2026', '01/08/2026', '01/10/2026'], $datos['labels']);
        $this->assertSame([12, 17, 21], $datos['datasets'][0]['data']);
        $this->assertSame([20, 20, 20], $datos['datasets'][1]['data']);
    }

    public function test_la_ficha_muestra_el_grafico_solo_si_hay_cuestionarios(): void
    {
        $url = route('filament.admin.resources.pacientes.view', ['record' => $this->paciente]);

        $this->get($url)->assertOk()->assertDontSee('Evolución del control del asma');

        CuestionarioAct::factory()->for($this->paciente)->create();

        $this->get($url)->assertOk()->assertSee('Evolución del control del asma');
    }

    private function gestor(): Testable
    {
        return Livewire::test(CuestionariosActRelationManager::class, [
            'ownerRecord' => $this->paciente,
            'pageClass' => ViewPaciente::class,
        ]);
    }
}
