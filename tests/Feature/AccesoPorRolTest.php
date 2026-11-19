<?php

namespace Tests\Feature;

use App\Models\Adjunto;
use App\Models\Alergeno;
use App\Models\Alergia;
use App\Models\Aplicacion;
use App\Models\Cita;
use App\Models\Consentimiento;
use App\Models\CuestionarioAct;
use App\Models\Diagnostico;
use App\Models\DocumentoEmitido;
use App\Models\Frasco;
use App\Models\HistoriaClinica;
use App\Models\Inmunoterapia;
use App\Models\NotaEvolucion;
use App\Models\Paciente;
use App\Models\Preconsulta;
use App\Models\PruebaCutanea;
use App\Models\ReaccionSistemica;
use App\Models\Receta;
use App\Models\ReglaAtraso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_recepcion_usa_la_agenda_pero_no_ve_tratamientos_ni_notas(): void
    {
        $recepcion = User::factory()->recepcion()->create();

        $this->actingAs($recepcion)->get('/panel/agenda')->assertOk();
        $this->actingAs($recepcion)->get('/panel/agenda/semana')->assertOk();
        $this->actingAs($recepcion)->get('/panel/notas-evolucion')->assertForbidden();
        $this->actingAs($recepcion)->get('/panel/inmunoterapias')->assertForbidden();
        $this->actingAs($recepcion)->get('/panel/reglas-atraso')->assertForbidden();
    }

    public function test_la_doctora_entra_a_todas_las_secciones(): void
    {
        $doctora = User::factory()->doctora()->create();

        foreach (['/panel/pacientes', '/panel/historias-clinicas', '/panel/prick-test', '/panel/alergenos', '/panel/usuarios', '/panel/agenda', '/panel/notas-evolucion', '/panel/inmunoterapias', '/panel/reglas-atraso'] as $ruta) {
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
            'notas: ver' => [NotaEvolucion::class, 'viewAny', true, false],
            'notas: borrar' => [NotaEvolucion::class, 'delete', false, false],
            'inmunoterapia: crear' => [Inmunoterapia::class, 'create', true, false],
            'inmunoterapia: borrar' => [Inmunoterapia::class, 'delete', false, false],
            'frascos: crear' => [Frasco::class, 'create', true, false],
            'aplicaciones: crear' => [Aplicacion::class, 'create', true, false],
            'aplicaciones: borrar' => [Aplicacion::class, 'delete', false, false],
            'reacción sistémica: crear' => [ReaccionSistemica::class, 'create', true, false],
            'reglas de atraso: editar' => [ReglaAtraso::class, 'update', true, false],
            'citas: crear' => [Cita::class, 'create', true, true],
            'citas: editar' => [Cita::class, 'update', true, true],
            'citas: borrar' => [Cita::class, 'delete', false, false],
            'estudios: ver' => [Adjunto::class, 'viewAny', true, false],
            'estudios: subir' => [Adjunto::class, 'create', true, false],
            'estudios: borrar' => [Adjunto::class, 'delete', false, false],
            'ACT: registrar' => [CuestionarioAct::class, 'create', true, false],
            'consentimiento: generar' => [Consentimiento::class, 'create', true, false],
            'consentimiento: modificar' => [Consentimiento::class, 'update', false, false],
            'consentimiento: registrar firma' => [Consentimiento::class, 'firmar', true, false],
            'recetas: crear' => [Receta::class, 'create', true, false],
            'recetas: borrar' => [Receta::class, 'delete', false, false],
            'documentos emitidos: ver' => [DocumentoEmitido::class, 'viewAny', true, false],
            'documentos emitidos: crear a mano' => [DocumentoEmitido::class, 'create', false, false],
            'pre-consulta: preparar tablet' => [Preconsulta::class, 'create', true, true],
            'pre-consulta: ver respuestas' => [Preconsulta::class, 'viewAny', true, false],
        ];
    }

    /**
     * @param  class-string  $modelo
     */
    #[DataProvider('matrizDePermisos')]
    public function test_matriz_de_permisos_por_rol(string $modelo, string $habilidad, bool $doctora, bool $recepcion): void
    {
        // La factory de estudios escribe un archivo: que vaya a un disco temporal.
        Storage::fake(Adjunto::DISCO);

        $necesitaRegistro = ! in_array($habilidad, ['viewAny', 'create'], true);
        $argumento = $necesitaRegistro ? $modelo::factory()->make() : $modelo;

        $this->assertSame($doctora, User::factory()->doctora()->make()->can($habilidad, $argumento), 'doctora');
        $this->assertSame($recepcion, User::factory()->recepcion()->make()->can($habilidad, $argumento), 'recepción');
    }
}
