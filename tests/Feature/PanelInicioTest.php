<?php

namespace Tests\Feature;

use App\Filament\Widgets\AplicacionesDeHoy;
use App\Filament\Widgets\CitasDeHoy;
use App\Filament\Widgets\PacientesAtrasados;
use App\Models\Aplicacion;
use App\Models\Cita;
use App\Models\Inmunoterapia;
use App\Models\Paciente;
use App\Models\ReglaAtraso;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * HU-15: panel de inicio con lo que pasa hoy en el consultorio.
 */
class PanelInicioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-23 07:00'));
    }

    public function test_ambos_roles_entran_al_panel(): void
    {
        $this->actingAs(User::factory()->doctora()->create())->get('/panel')->assertOk();
        $this->actingAs(User::factory()->recepcion()->create())->get('/panel')->assertOk();
    }

    public function test_citas_de_hoy_muestra_las_vigentes_y_oculta_las_canceladas(): void
    {
        $this->actingAs(User::factory()->recepcion()->create());

        $deHoy = Cita::factory()->create(['inicio' => '2026-11-23 10:00', 'fin' => '2026-11-23 10:30']);
        $cancelada = Cita::factory()->create([
            'inicio' => '2026-11-23 11:00',
            'fin' => '2026-11-23 11:30',
            'estado' => 'cancelada',
            'motivo_cancelacion' => 'Viaje.',
        ]);
        $deManana = Cita::factory()->create(['inicio' => '2026-11-24 10:00', 'fin' => '2026-11-24 10:30']);

        Livewire::test(CitasDeHoy::class)
            ->assertCanSeeTableRecords([$deHoy])
            ->assertCanNotSeeTableRecords([$cancelada, $deManana]);
    }

    public function test_la_recepcion_no_ve_los_widgets_clinicos(): void
    {
        $this->actingAs(User::factory()->recepcion()->create());

        $this->assertFalse(AplicacionesDeHoy::canView());
        $this->assertFalse(PacientesAtrasados::canView());
        $this->assertTrue(CitasDeHoy::canView());
    }

    public function test_la_doctora_ve_a_quien_le_toca_hoy_y_a_los_atrasados_con_su_regla(): void
    {
        $this->actingAs(User::factory()->doctora()->create());
        ReglaAtraso::factory()->create(['dias_desde' => 7, 'dias_hasta' => 14, 'accion' => 'Reducir la dosis un 25 %.']);

        $leTocaHoy = $this->inmunoterapiaCon('Carla', 'Mamani', diasDesdeLaUltima: 7);
        $atrasado = $this->inmunoterapiaCon('Jorge', 'Vargas', diasDesdeLaUltima: 20);

        $this->assertTrue(AplicacionesDeHoy::canView());

        Livewire::test(AplicacionesDeHoy::class)
            ->assertSee('Mamani')
            ->assertDontSee('Vargas');

        Livewire::test(PacientesAtrasados::class)
            ->assertSee('Vargas')
            ->assertSee('13 días')
            ->assertSee('Reducir la dosis un 25 %.')
            ->assertDontSee('Mamani');

        $this->assertNotNull($leTocaHoy);
        $this->assertNotNull($atrasado);
    }

    private function inmunoterapiaCon(string $nombres, string $apellido, int $diasDesdeLaUltima): Inmunoterapia
    {
        $paciente = Paciente::factory()->create(['nombres' => $nombres, 'apellido_paterno' => $apellido]);
        $inmunoterapia = Inmunoterapia::factory()->for($paciente)->create();
        $inmunoterapia->esquema()->createMany(Inmunoterapia::esquemaConvencional());

        Aplicacion::factory()->for($inmunoterapia)->create([
            'esquema_dosis_id' => $inmunoterapia->esquema()->value('id'),
            'aplicada_en' => now()->subDays($diasDesdeLaUltima)->setTime(10, 0),
        ]);

        return $inmunoterapia;
    }
}
