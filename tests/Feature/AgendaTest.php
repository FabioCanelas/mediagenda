<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\TipoCita;
use App\Filament\Resources\Citas\CitaResource;
use App\Filament\Resources\Citas\Pages\CreateCita;
use App\Filament\Resources\Citas\Pages\ListCitas;
use App\Filament\Resources\Citas\Schemas\CitaForm;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * HU-14: agenda sin choques de horario. La usa también la recepción.
 */
class AgendaTest extends TestCase
{
    use RefreshDatabase;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();

        // Lunes antes de abrir el consultorio: todos los horarios del día están por venir.
        $this->travelTo(CarbonImmutable::parse('2026-11-23 07:00'));
        $this->actingAs(User::factory()->recepcion()->create());
        $this->paciente = Paciente::factory()->create(['nombres' => 'Lucía', 'apellido_paterno' => 'Quispe']);
    }

    public function test_recepcion_agenda_una_cita(): void
    {
        Livewire::test(CreateCita::class)
            ->fillForm([
                'paciente_id' => $this->paciente->getKey(),
                'tipo' => TipoCita::Control->value,
                'inicio' => '2026-11-23 09:00:00',
                'fin' => '2026-11-23 09:30:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $cita = Cita::query()->sole();

        $this->assertSame(EstadoCita::Programada, $cita->estado);
        $this->assertSame('09:00', $cita->inicio->format('H:i'));
    }

    public function test_no_permite_citas_que_se_cruzan_y_sugiere_horarios_libres(): void
    {
        $this->citaDe('09:00', '09:30');

        Livewire::test(CreateCita::class)
            ->fillForm([
                'paciente_id' => $this->paciente->getKey(),
                'tipo' => TipoCita::Control->value,
                'inicio' => '2026-11-23 09:15:00',
                'fin' => '2026-11-23 09:45:00',
            ])
            ->call('create')
            ->assertHasFormErrors(['inicio']);

        $this->assertSame(1, Cita::query()->count());
        $this->assertSame(
            'Ese horario se cruza con otra cita. Horarios libres: 08:00, 08:15, 08:30, 09:30, 09:45.',
            CitaForm::mensajeChoque('2026-11-23 09:15', '2026-11-23 09:45', TipoCita::Control->value),
        );
    }

    public function test_una_cita_que_empieza_cuando_termina_otra_no_se_cruza(): void
    {
        $this->citaDe('09:00', '09:30');

        $this->assertFalse(Cita::seCruza(CarbonImmutable::parse('2026-11-23 09:30'), CarbonImmutable::parse('2026-11-23 10:00')));
    }

    public function test_cancelar_pide_el_motivo_y_la_cita_queda_en_el_historial(): void
    {
        $cita = $this->citaDe('09:00', '09:30');

        Livewire::test(ListCitas::class)
            ->callAction(TestAction::make('cancelar')->table($cita), data: ['motivo_cancelacion' => ''])
            ->assertHasFormErrors(['motivo_cancelacion' => 'required']);

        Livewire::test(ListCitas::class)
            ->callAction(TestAction::make('cancelar')->table($cita), data: ['motivo_cancelacion' => 'El paciente está de viaje.'])
            ->assertHasNoFormErrors();

        $cita->refresh();

        $this->assertSame(EstadoCita::Cancelada, $cita->estado);
        $this->assertSame('El paciente está de viaje.', $cita->motivo_cancelacion);
        $this->assertFalse(auth()->user()->can('delete', $cita));
    }

    public function test_una_cita_cancelada_libera_el_horario(): void
    {
        $this->citaDe('09:00', '09:30')->update([
            'estado' => EstadoCita::Cancelada,
            'motivo_cancelacion' => 'Reprogramada.',
        ]);

        $this->assertFalse(Cita::seCruza(CarbonImmutable::parse('2026-11-23 09:00'), CarbonImmutable::parse('2026-11-23 09:30')));
    }

    public function test_confirmar_y_marcar_atendida(): void
    {
        $cita = $this->citaDe('09:00', '09:30');

        Livewire::test(ListCitas::class)
            ->callAction(TestAction::make('confirmar')->table($cita));

        $this->assertSame(EstadoCita::Confirmada, $cita->refresh()->estado);

        Livewire::test(ListCitas::class)
            ->assertActionHidden(TestAction::make('confirmar')->table($cita))
            ->callAction(TestAction::make('atendida')->table($cita));

        $this->assertSame(EstadoCita::Atendida, $cita->refresh()->estado);
    }

    public function test_la_agenda_semanal_muestra_las_citas_de_la_semana(): void
    {
        $this->citaDe('09:00', '09:30');

        $this->get(CitaResource::getUrl('semana'))
            ->assertOk()
            ->assertSee('Quispe');
    }

    private function citaDe(string $inicio, string $fin): Cita
    {
        return Cita::factory()->for($this->paciente)->create([
            'inicio' => "2026-11-23 {$inicio}",
            'fin' => "2026-11-23 {$fin}",
        ]);
    }
}
