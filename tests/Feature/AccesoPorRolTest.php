<?php

namespace Tests\Feature;

use App\Models\Alergeno;
use App\Models\Alergia;
use App\Models\Diagnostico;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Models\PruebaCutanea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * HU-01: inicio de sesión y roles.
 */
class AccesoPorRolTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_sesion_redirige_al_login(): void
    {
        $this->get('/panel/pacientes')->assertRedirect('/panel/login');
    }

    public function test_un_usuario_desactivado_no_puede_entrar_al_panel(): void
    {
        $inactiva = User::factory()->recepcion()->inactivo()->create();

        $this->actingAs($inactiva)->get('/panel')->assertForbidden();
    }

    public function test_recepcion_ve_pacientes_pero_no_la_informacion_clinica(): void
    {
        $recepcion = User::factory()->recepcion()->create();

        $this->actingAs($recepcion)->get('/panel/pacientes')->assertOk();
        $this->actingAs($recepcion)->get('/panel/historias-clinicas')->assertForbidden();
        $this->actingAs($recepcion)->get('/panel/prick-test')->assertForbidden();
        $this->actingAs($recepcion)->get('/panel/usuarios')->assertForbidden();
    }

    public function test_la_doctora_entra_a_todas_las_secciones(): void
    {
        $doctora = User::factory()->doctora()->create();

        foreach (['/panel/pacientes', '/panel/historias-clinicas', '/panel/prick-test', '/panel/alergenos', '/panel/usuarios'] as $ruta) {
            $this->actingAs($doctora)->get($ruta)->assertOk();
        }
    }

    /**
     * Matriz completa de permisos: [modelo, habilidad, doctora, recepción].
     *
     * @return array<string, array{class-string, string, bool, bool}>
     */
    public static function matrizDePermisos(): array
    {
        return [
            'pacientes: ver' => [Paciente::class, 'viewAny', true, true],
            'pacientes: crear' => [Paciente::class, 'create', true, true],
            'pacientes: ocultar' => [Paciente::class, 'delete', true, false],
            'pacientes: borrar definitivo' => [Paciente::class, 'forceDelete', false, false],
            'historia: ver' => [HistoriaClinica::class, 'viewAny', true, false],
            'historia: borrar' => [HistoriaClinica::class, 'delete', false, false],
            'diagnóstico: crear' => [Diagnostico::class, 'create', true, false],
            'alergias: ver' => [Alergia::class, 'viewAny', true, false],
            'alergias: borrar' => [Alergia::class, 'delete', false, false],
            'prick test: crear' => [PruebaCutanea::class, 'create', true, false],
            'prick test: borrar' => [PruebaCutanea::class, 'delete', false, false],
            'alérgenos: editar' => [Alergeno::class, 'update', true, false],
            'usuarios: crear' => [User::class, 'create', true, false],
            'usuarios: borrar' => [User::class, 'delete', false, false],
        ];
    }

    /**
     * @param  class-string  $modelo
     */
    #[DataProvider('matrizDePermisos')]
    public function test_matriz_de_permisos_por_rol(string $modelo, string $habilidad, bool $doctora, bool $recepcion): void
    {
        $necesitaRegistro = ! in_array($habilidad, ['viewAny', 'create'], true);
        $argumento = $necesitaRegistro ? $modelo::factory()->make() : $modelo;

        $this->assertSame($doctora, User::factory()->doctora()->make()->can($habilidad, $argumento), 'doctora');
        $this->assertSame($recepcion, User::factory()->recepcion()->make()->can($habilidad, $argumento), 'recepción');
    }
}
