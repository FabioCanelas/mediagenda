<?php

namespace Tests\Feature;

use App\Filament\Resources\Pacientes\Pages\CreatePaciente;
use App\Filament\Resources\Pacientes\Pages\EditPaciente;
use App\Filament\Resources\Pacientes\Pages\ListPacientes;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * HU-02: registro y búsqueda de pacientes.
 */
class RegistroPacienteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->recepcion()->create());
    }

    /**
     * @return array<string, mixed>
     */
    private function datosValidos(array $cambios = []): array
    {
        return [
            'ci' => '4567890',
            'ci_complemento' => '',
            'nombres' => 'Ana María',
            'apellido_paterno' => 'Quispe',
            'apellido_materno' => 'Mamani',
            'fecha_nacimiento' => '1990-05-12',
            'sexo' => 'F',
            'celular' => '71234567',
            ...$cambios,
        ];
    }

    public function test_registra_un_paciente_nuevo(): void
    {
        Livewire::test(CreatePaciente::class)
            ->fillForm($this->datosValidos())
            ->call('create')
            ->assertHasNoFormErrors();

        $paciente = Paciente::firstWhere('ci', '4567890');
        $this->assertSame('Ana María Quispe Mamani', $paciente->nombre_completo);
        $this->assertSame(auth()->id(), $paciente->created_by);
    }

    public function test_un_ci_repetido_no_crea_otro_paciente_y_avisa(): void
    {
        $existente = Paciente::factory()->create(['ci' => '4567890']);

        Livewire::test(CreatePaciente::class)
            ->fillForm($this->datosValidos(['nombres' => 'Otra Persona']))
            ->call('create')
            ->assertNotified('Este paciente ya está registrado');

        $this->assertSame(1, Paciente::where('ci', '4567890')->count());
        $this->assertTrue(Paciente::first()->is($existente));
    }

    public function test_el_mismo_ci_con_otro_complemento_es_otro_paciente(): void
    {
        Paciente::factory()->create(['ci' => '4567890']);

        Livewire::test(CreatePaciente::class)
            ->fillForm($this->datosValidos(['ci_complemento' => '1b']))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pacientes', ['ci' => '4567890', 'ci_complemento' => '1B']);
    }

    public function test_al_editar_no_se_puede_usar_el_ci_de_otro_paciente(): void
    {
        Paciente::factory()->create(['ci' => '4567890']);
        $paciente = Paciente::factory()->create(['ci' => '1234567']);

        Livewire::test(EditPaciente::class, ['record' => $paciente->getRouteKey()])
            ->fillForm(['ci' => '4567890'])
            ->call('save')
            ->assertHasFormErrors(['ci' => 'unique']);

        $this->assertSame('1234567', $paciente->fresh()->ci);
    }

    public function test_un_menor_de_edad_necesita_los_datos_del_tutor(): void
    {
        Livewire::test(CreatePaciente::class)
            ->fillForm($this->datosValidos(['fecha_nacimiento' => now()->subYears(10)->toDateString()]))
            ->call('create')
            ->assertHasFormErrors(['tutor_nombre' => 'required', 'tutor_ci' => 'required', 'tutor_parentesco' => 'required']);

        $this->assertDatabaseCount('pacientes', 0);
    }

    public function test_el_ci_solo_admite_numeros(): void
    {
        Livewire::test(CreatePaciente::class)
            ->fillForm($this->datosValidos(['ci' => '45A78-9']))
            ->call('create')
            ->assertHasFormErrors(['ci' => 'regex']);
    }

    public function test_exige_al_menos_un_apellido(): void
    {
        Livewire::test(CreatePaciente::class)
            ->fillForm($this->datosValidos(['apellido_paterno' => null, 'apellido_materno' => null]))
            ->call('create')
            ->assertHasFormErrors(['apellido_paterno' => 'required_without']);
    }

    public function test_busca_pacientes_por_apellido_o_por_ci(): void
    {
        $buscado = Paciente::factory()->create(['apellido_paterno' => 'Choque', 'ci' => '7654321']);
        $otro = Paciente::factory()->create(['apellido_paterno' => 'Rojas', 'ci' => '1112223']);

        Livewire::test(ListPacientes::class)
            ->searchTable('Choque')
            ->assertCanSeeTableRecords([$buscado])
            ->assertCanNotSeeTableRecords([$otro])
            ->searchTable('765432')
            ->assertCanSeeTableRecords([$buscado])
            ->assertCanNotSeeTableRecords([$otro]);
    }
}
