<?php

namespace Tests\Feature;

use App\Enums\EstadoNota;
use App\Filament\Resources\NotasEvolucion\NotaEvolucionResource;
use App\Filament\Resources\NotasEvolucion\Pages\CreateNotaEvolucion;
use App\Filament\Resources\NotasEvolucion\Pages\ViewNotaEvolucion;
use App\Models\NotaEvolucion;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * HU-06: notas de evolución que se cierran y solo admiten anexos.
 */
class NotasEvolucionTest extends TestCase
{
    use RefreshDatabase;

    private User $doctora;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctora = User::factory()->doctora()->create();
        $this->actingAs($this->doctora);
    }

    public function test_la_doctora_registra_una_nota_en_borrador(): void
    {
        $paciente = Paciente::factory()->create();

        Livewire::test(CreateNotaEvolucion::class)
            ->fillForm([
                'paciente_id' => $paciente->getKey(),
                'fecha' => now()->toDateString(),
                'evolucion' => 'Mejoría de la rinitis con el antihistamínico.',
                'frecuencia_cardiaca' => 72,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $nota = NotaEvolucion::query()->sole();

        $this->assertSame(EstadoNota::Borrador, $nota->estado);
        $this->assertSame($paciente->getKey(), $nota->paciente_id);
    }

    public function test_los_signos_vitales_fuera_de_rango_no_se_aceptan(): void
    {
        Livewire::test(CreateNotaEvolucion::class)
            ->fillForm([
                'paciente_id' => Paciente::factory()->create()->getKey(),
                'evolucion' => 'Control.',
                'temperatura' => 60,
            ])
            ->call('create')
            ->assertHasFormErrors(['temperatura' => 'max']);
    }

    public function test_al_cerrar_la_nota_queda_registrado_quien_y_cuando(): void
    {
        $nota = NotaEvolucion::factory()->create();

        Livewire::test(ViewNotaEvolucion::class, ['record' => $nota->getRouteKey()])
            ->assertActionVisible('cerrarNota')
            ->assertActionHidden('agregarAnexo')
            ->callAction('cerrarNota')
            ->assertNotified('Nota cerrada');

        $nota->refresh();

        $this->assertTrue($nota->estaCerrada());
        $this->assertSame($this->doctora->getKey(), $nota->cerrada_por);
        $this->assertNotNull($nota->cerrada_en);
    }

    public function test_una_nota_cerrada_no_se_puede_editar(): void
    {
        $nota = NotaEvolucion::factory()->create();
        $nota->cerrar($this->doctora);

        $this->assertFalse($this->doctora->can('update', $nota));
        $this->get(NotaEvolucionResource::getUrl('edit', ['record' => $nota]))->assertForbidden();
    }

    public function test_una_nota_cerrada_solo_admite_anexos_y_el_texto_original_no_cambia(): void
    {
        $nota = NotaEvolucion::factory()->create(['evolucion' => 'Texto original.']);
        $nota->cerrar($this->doctora);

        Livewire::test(ViewNotaEvolucion::class, ['record' => $nota->getRouteKey()])
            ->assertActionHidden('cerrarNota')
            ->callAction('agregarAnexo', data: ['texto' => 'Corrección: la dosis es cada 24 horas.'])
            ->assertHasNoFormErrors()
            ->assertNotified('Anexo agregado');

        $this->assertDatabaseHas('notas_evolucion_anexos', [
            'nota_evolucion_id' => $nota->getKey(),
            'texto' => 'Corrección: la dosis es cada 24 horas.',
        ]);
        $this->assertSame('Texto original.', $nota->refresh()->evolucion);
    }

    public function test_los_anexos_no_se_editan_ni_se_borran(): void
    {
        $nota = NotaEvolucion::factory()->create();
        $nota->cerrar($this->doctora);
        $anexo = $nota->anexos()->create(['texto' => 'Corrección.']);

        $this->assertFalse($this->doctora->can('update', $anexo));
        $this->assertFalse($this->doctora->can('delete', $anexo));
    }
}
