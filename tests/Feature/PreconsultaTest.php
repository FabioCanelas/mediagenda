<?php

namespace Tests\Feature;

use App\Enums\CategoriaAlergia;
use App\Enums\EstadoAlergia;
use App\Enums\EstadoPreconsulta;
use App\Filament\Preconsulta\FormularioPreconsulta;
use App\Filament\Resources\Pacientes\Pages\ViewPaciente;
use App\Filament\Widgets\PreconsultasPorRevisar;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Models\Preconsulta;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * HU-05: pre-consulta que el paciente llena en la tablet.
 */
class PreconsultaTest extends TestCase
{
    use RefreshDatabase;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-23 09:00'));
        $this->paciente = Paciente::factory()->create(['nombres' => 'Lucía', 'celular' => '70000001']);
    }

    public function test_preparar_la_tablet_crea_un_enlace_que_solo_se_guarda_como_hash(): void
    {
        ['preconsulta' => $preconsulta, 'token' => $token] = Preconsulta::preparar($this->paciente);

        $this->assertSame(48, strlen($token));
        $this->assertSame(hash('sha256', $token), $preconsulta->token_hash);
        $this->assertDatabaseMissing('preconsultas', ['token_hash' => $token]);
        $this->assertSame('2026-11-23 12:00', $preconsulta->expira_en->format('Y-m-d H:i'));
        $this->assertArrayNotHasKey('token_hash', $preconsulta->toArray());
    }

    public function test_un_enlace_nuevo_anula_el_anterior_sin_usar(): void
    {
        ['token' => $anterior] = Preconsulta::preparar($this->paciente);
        ['token' => $nuevo] = Preconsulta::preparar($this->paciente);

        $this->assertNull(Preconsulta::vigentePorToken($anterior));
        $this->assertNotNull(Preconsulta::vigentePorToken($nuevo));
        $this->assertSame(1, Preconsulta::query()->where('estado', EstadoPreconsulta::Descartada)->count());
    }

    public function test_la_recepcion_prepara_la_tablet_desde_la_ficha(): void
    {
        $this->actingAs(User::factory()->recepcion()->create());

        Livewire::test(ViewPaciente::class, ['record' => $this->paciente->getRouteKey()])
            ->assertActionVisible('prepararTablet')
            ->assertActionHidden('revisarPreconsulta')
            ->mountAction('prepararTablet')
            ->assertActionMounted('prepararTablet')
            ->assertActionDataSet(['vence' => 'hoy a las 12:00']);

        $preconsulta = $this->paciente->preconsultas()->sole();

        $this->assertSame(EstadoPreconsulta::Pendiente, $preconsulta->estado);
        $this->assertSame('70000001', $preconsulta->celular);
    }

    public function test_si_el_enlace_vence_despues_de_medianoche_dice_manana(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-23 22:40'));
        $this->actingAs(User::factory()->recepcion()->create());

        Livewire::test(ViewPaciente::class, ['record' => $this->paciente->getRouteKey()])
            ->mountAction('prepararTablet')
            ->assertActionDataSet(['vence' => 'mañana a las 01:40']);
    }

    public function test_el_paciente_abre_el_formulario_sin_iniciar_sesion(): void
    {
        ['token' => $token] = Preconsulta::preparar($this->paciente);

        $this->get(Preconsulta::enlace($token))
            ->assertOk()
            ->assertSee('Hola, Lucía')
            ->assertSee('¿Por qué viene a la consulta?')
            ->assertDontSee($this->paciente->ci);
    }

    public function test_abrir_el_enlace_cierra_la_sesion_del_personal_en_ese_navegador(): void
    {
        ['token' => $token] = Preconsulta::preparar($this->paciente);

        $this->actingAs(User::factory()->recepcion()->create())
            ->get(Preconsulta::enlace($token))
            ->assertOk();

        $this->assertGuest();
    }

    public function test_un_enlace_vencido_o_inventado_no_sirve(): void
    {
        ['token' => $token] = Preconsulta::preparar($this->paciente);

        $this->get(Preconsulta::enlace(str_repeat('a', 48)))->assertOk()->assertSee('Este enlace ya no sirve');
        $this->get('/panel/preconsulta/corto')->assertNotFound();

        $this->travel(3)->hours();

        $this->get(Preconsulta::enlace($token))->assertOk()->assertSee('Este enlace ya no sirve');
    }

    public function test_el_paciente_envia_sus_respuestas_y_el_enlace_deja_de_servir(): void
    {
        ['preconsulta' => $preconsulta, 'token' => $token] = Preconsulta::preparar($this->paciente);

        Livewire::test(FormularioPreconsulta::class, ['token' => $token])
            ->fillForm($this->respuestas())
            ->call('enviar')
            ->assertHasNoFormErrors()
            ->assertSet('etapa', 'enviada')
            ->assertSee('¡Gracias!');

        $preconsulta->refresh();

        $this->assertSame(EstadoPreconsulta::Enviada, $preconsulta->estado);
        $this->assertSame('Estornudos y nariz tapada en las mañanas.', $preconsulta->motivo_consulta);
        $this->assertSame(['nariz', 'pecho'], $preconsulta->sintomas);
        $this->assertSame('71234567', $preconsulta->celular);
        $this->assertNotNull($preconsulta->enviada_en);

        Livewire::test(FormularioPreconsulta::class, ['token' => $token])
            ->assertSet('etapa', 'invalida');
    }

    public function test_marca_las_preguntas_obligatorias_que_faltan(): void
    {
        ['preconsulta' => $preconsulta, 'token' => $token] = Preconsulta::preparar($this->paciente);

        Livewire::test(FormularioPreconsulta::class, ['token' => $token])
            ->fillForm(['celular' => '71234567'])
            ->call('enviar')
            ->assertHasFormErrors([
                'motivo_consulta' => 'required',
                'tiempo_evolucion' => 'required',
                'alergia_medicamentos' => 'required',
                'fuma' => 'required',
            ])
            ->assertSet('etapa', 'formulario');

        $this->assertSame(EstadoPreconsulta::Pendiente, $preconsulta->refresh()->estado);
    }

    public function test_si_es_alergico_a_un_medicamento_pide_cual(): void
    {
        ['token' => $token] = Preconsulta::preparar($this->paciente);

        Livewire::test(FormularioPreconsulta::class, ['token' => $token])
            ->fillForm([...$this->respuestas(), 'alergia_medicamentos' => 'si', 'alergia_medicamentos_detalle' => null])
            ->call('enviar')
            ->assertHasFormErrors(['alergia_medicamentos_detalle' => 'required']);
    }

    public function test_la_doctora_pasa_las_respuestas_a_la_historia_clinica(): void
    {
        $this->actingAs($doctora = User::factory()->doctora()->create());
        $preconsulta = Preconsulta::factory()->for($this->paciente)->enviada()->create();

        $this->assertTrue(PreconsultasPorRevisar::canView());

        Livewire::test(ViewPaciente::class, ['record' => $this->paciente->getRouteKey()])
            ->assertActionVisible('revisarPreconsulta')
            ->callAction('revisarPreconsulta')
            ->assertHasNoFormErrors()
            ->assertNotified('Respuestas pasadas a la historia clínica');

        $historia = $this->paciente->historiaClinica()->sole();
        $alergia = $this->paciente->alergias()->sole();
        $preconsulta->refresh();

        $this->assertStringContainsString('Estornudos y nariz tapada todas las mañanas.', $historia->padecimiento_actual);
        $this->assertStringContainsString('Desde: Hace 2 años.', $historia->padecimiento_actual);
        $this->assertSame('Madre con asma.', $historia->antecedentes_heredofamiliares);
        $this->assertStringContainsString('Refiere alergia a medicamentos: Penicilina.', $historia->antecedentes_personales_patologicos);
        $this->assertStringContainsString('Mascotas: Un gato.', $historia->antecedentes_personales_no_patologicos);

        $this->assertSame('Penicilina', $alergia->sustancia);
        $this->assertSame(CategoriaAlergia::Medicamento, $alergia->categoria);
        $this->assertSame(EstadoAlergia::Sospechada, $alergia->estado);

        $this->assertSame('71234567', $this->paciente->refresh()->celular);
        $this->assertSame(EstadoPreconsulta::Revisada, $preconsulta->estado);
        $this->assertSame($doctora->getKey(), $preconsulta->revisada_por);
    }

    public function test_si_la_historia_ya_tiene_texto_lo_agrega_al_final_con_la_fecha(): void
    {
        $this->actingAs($doctora = User::factory()->doctora()->create());
        HistoriaClinica::factory()->for($this->paciente)->create(['antecedentes_heredofamiliares' => 'Padre con rinitis.']);
        $preconsulta = Preconsulta::factory()->for($this->paciente)->enviada()->create();

        $preconsulta->pasarAHistoria($doctora, ['historia' => $preconsulta->propuestaParaHistoria()]);

        $this->assertSame(
            "Padre con rinitis.\n\nPre-consulta del 23/11/2026: Madre con asma.",
            $this->paciente->historiaClinica()->sole()->antecedentes_heredofamiliares,
        );
    }

    public function test_la_propuesta_no_repite_puntos_y_pone_los_sintomas_en_minuscula(): void
    {
        $preconsulta = Preconsulta::factory()->for($this->paciente)->enviada()->create([
            'motivo_consulta' => 'Tos en las noches.',
            'medicamentos_actuales' => 'Loratadina cuando tiene síntomas.',
            'sintomas' => ['pecho'],
        ]);

        $propuesta = $preconsulta->propuestaParaHistoria();

        $this->assertSame('Tos en las noches. Desde: Hace 2 años. Síntomas referidos: tos, silbido en el pecho o falta de aire.', $propuesta['padecimiento_actual']);
        $this->assertStringContainsString('Medicamentos actuales: Loratadina cuando tiene síntomas.', $propuesta['antecedentes_personales_patologicos']);
        $this->assertStringNotContainsString('..', implode(' ', array_filter($propuesta)));
    }

    public function test_la_doctora_puede_descartarla(): void
    {
        $this->actingAs(User::factory()->doctora()->create());
        $preconsulta = Preconsulta::factory()->for($this->paciente)->enviada()->create();

        Livewire::test(ViewPaciente::class, ['record' => $this->paciente->getRouteKey()])
            ->callAction(TestAction::make('revisarPreconsulta')->arguments(['descartar' => true]))
            ->assertNotified('Pre-consulta descartada');

        $this->assertSame(EstadoPreconsulta::Descartada, $preconsulta->refresh()->estado);
        $this->assertNull($this->paciente->historiaClinica);
    }

    public function test_la_recepcion_no_ve_las_respuestas(): void
    {
        $recepcion = User::factory()->recepcion()->create();
        $preconsulta = Preconsulta::factory()->for($this->paciente)->enviada()->create();

        $this->assertTrue($recepcion->can('create', Preconsulta::class));
        $this->assertFalse($recepcion->can('viewAny', Preconsulta::class));
        $this->assertFalse($recepcion->can('update', $preconsulta));

        $this->actingAs($recepcion);
        $this->assertFalse(PreconsultasPorRevisar::canView());
    }

    /**
     * @return array<string, mixed>
     */
    private function respuestas(): array
    {
        return [
            'motivo_consulta' => 'Estornudos y nariz tapada en las mañanas.',
            'tiempo_evolucion' => 'Hace 1 año',
            'sintomas' => ['nariz', 'pecho'],
            'alergia_medicamentos' => 'no',
            'fuma' => 0,
            'celular' => '71234567',
        ];
    }
}
