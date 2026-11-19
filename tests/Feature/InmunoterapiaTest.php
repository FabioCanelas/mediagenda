<?php

namespace Tests\Feature;

use App\Enums\ColorFrasco;
use App\Enums\Dilucion;
use App\Enums\GradoWao;
use App\Filament\Resources\Inmunoterapias\Pages\ViewInmunoterapia;
use App\Filament\Resources\Inmunoterapias\RelationManagers\AplicacionesRelationManager;
use App\Models\Aplicacion;
use App\Models\EsquemaDosis;
use App\Models\Frasco;
use App\Models\Inmunoterapia;
use App\Models\ReglaAtraso;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * HU-09 a HU-13: esquema, frascos, aplicaciones, reacciones y atrasos.
 */
class InmunoterapiaTest extends TestCase
{
    use RefreshDatabase;

    private Inmunoterapia $inmunoterapia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-23 10:00'));
        $this->actingAs(User::factory()->doctora()->create());

        $this->inmunoterapia = Inmunoterapia::factory()->create(['fecha_inicio' => now()->toDateString()]);
        $this->inmunoterapia->esquema()->createMany(Inmunoterapia::esquemaConvencional());

        foreach ([Dilucion::Mil, Dilucion::Cien] as $dilucion) {
            Frasco::factory()->for($this->inmunoterapia)->create(['dilucion' => $dilucion]);
        }
    }

    /**
     * @return array<string, array{Dilucion, ColorFrasco}>
     */
    public static function coloresPorDilucion(): array
    {
        return [
            '1:10000' => [Dilucion::DiezMil, ColorFrasco::Plata],
            '1:1000' => [Dilucion::Mil, ColorFrasco::Verde],
            '1:100' => [Dilucion::Cien, ColorFrasco::Azul],
            '1:10' => [Dilucion::Diez, ColorFrasco::Amarillo],
            '1:1' => [Dilucion::Concentrado, ColorFrasco::Rojo],
        ];
    }

    #[DataProvider('coloresPorDilucion')]
    public function test_el_color_del_frasco_sale_de_la_dilucion(Dilucion $dilucion, ColorFrasco $color): void
    {
        $frasco = Frasco::factory()->for($this->inmunoterapia)->create(['dilucion' => $dilucion]);

        $this->assertSame($color, $frasco->color);
    }

    public function test_un_frasco_vencido_no_se_ofrece_para_aplicar(): void
    {
        $vencido = Frasco::factory()->for($this->inmunoterapia)->vencido()->create(['dilucion' => Dilucion::Diez]);
        $venceHoy = Frasco::factory()->for($this->inmunoterapia)->create(['dilucion' => Dilucion::Diez, 'fecha_vencimiento' => now()->toDateString()]);

        $this->assertFalse($vencido->sePuedeUsar());
        $this->assertTrue($venceHoy->sePuedeUsar());

        $utilizables = $this->inmunoterapia->frascos()->utilizables()->pluck('id');

        $this->assertNotContains($vencido->getKey(), $utilizables);
        $this->assertContains($venceHoy->getKey(), $utilizables);
    }

    public function test_un_frasco_dado_de_baja_no_se_puede_usar(): void
    {
        $frasco = Frasco::factory()->for($this->inmunoterapia)->create(['activo' => false]);

        $this->assertFalse($frasco->sePuedeUsar());
    }

    public function test_la_dosis_esperada_avanza_con_cada_aplicacion(): void
    {
        $primera = $this->inmunoterapia->dosisEsperada();

        $this->assertSame(1, $primera['esquema']->paso);
        $this->assertSame(Dilucion::Mil, $primera['dilucion']);
        $this->assertSame(0.05, $primera['volumen']);

        $this->aplicar();

        $segunda = $this->inmunoterapia->dosisEsperada();

        $this->assertSame(2, $segunda['esquema']->paso);
        $this->assertSame(0.10, $segunda['volumen']);
    }

    public function test_en_mantenimiento_la_dosis_esperada_es_la_del_frasco_concentrado(): void
    {
        $mantenimiento = Inmunoterapia::factory()->mantenimiento()->create(['dosis_mantenimiento_ml' => 0.40]);

        $esperada = $mantenimiento->dosisEsperada();

        $this->assertNull($esperada['esquema']);
        $this->assertSame(Dilucion::Concentrado, $esperada['dilucion']);
        $this->assertSame(0.40, $esperada['volumen']);
    }

    public function test_registra_la_aplicacion_con_la_dosis_del_esquema(): void
    {
        $this->gestorDeAplicaciones()
            ->callAction(TestAction::make('registrarAplicacion')->table(), data: ['brazo' => 'izquierdo'])
            ->assertHasNoFormErrors()
            ->assertNotified('Aplicación registrada');

        $aplicacion = $this->inmunoterapia->aplicaciones()->sole();

        $this->assertSame(1, $aplicacion->esquemaDosis->paso);
        $this->assertSame(Dilucion::Mil, $aplicacion->frasco->dilucion);
        $this->assertFalse($aplicacion->dosis_difiere_esquema);
        $this->assertSame('10:30', $aplicacion->observacion_hasta->format('H:i'));
        $this->assertSame(auth()->id(), $aplicacion->aplicada_por);
    }

    public function test_una_dosis_distinta_al_esquema_pide_confirmacion(): void
    {
        $this->gestorDeAplicaciones()
            ->callAction(TestAction::make('registrarAplicacion')->table(), data: ['brazo' => 'derecho', 'volumen_ml' => 0.10])
            ->assertHasFormErrors(['confirmar_dosis_distinta' => 'accepted']);

        $this->assertSame(0, $this->inmunoterapia->aplicaciones()->count());

        $this->gestorDeAplicaciones()
            ->callAction(TestAction::make('registrarAplicacion')->table(), data: [
                'brazo' => 'derecho',
                'volumen_ml' => 0.10,
                'confirmar_dosis_distinta' => true,
            ])
            ->assertHasNoFormErrors();

        $this->assertTrue($this->inmunoterapia->aplicaciones()->sole()->dosis_difiere_esquema);
    }

    public function test_la_observacion_de_30_minutos_se_termina_solo_al_cumplirse(): void
    {
        $aplicacion = $this->aplicar();

        $this->travel(29)->minutes();

        $this->assertFalse($aplicacion->puedeTerminarObservacion());
        $this->gestorDeAplicaciones()->assertActionDisabled(TestAction::make('terminarObservacion')->table($aplicacion));

        $this->travel(1)->minutes();

        $this->gestorDeAplicaciones()
            ->assertActionEnabled(TestAction::make('terminarObservacion')->table($aplicacion))
            ->callAction(TestAction::make('terminarObservacion')->table($aplicacion))
            ->assertNotified('Observación terminada');

        $this->assertTrue($aplicacion->refresh()->observacionCompletada());
    }

    public function test_el_modelo_no_deja_terminar_la_observacion_antes_de_tiempo(): void
    {
        $aplicacion = $this->aplicar();

        $this->expectException(LogicException::class);

        $aplicacion->terminarObservacion();
    }

    public function test_sugiere_la_siguiente_aplicacion_segun_el_intervalo_del_paso(): void
    {
        $aplicacion = $this->aplicar();

        $this->assertSame('2026-11-30', $aplicacion->fechaSiguienteSugerida()->toDateString());
    }

    public function test_registra_una_reaccion_sistemica_y_la_avisa_en_la_siguiente_dosis(): void
    {
        $aplicacion = $this->aplicar();

        $this->gestorDeAplicaciones()
            ->callAction(TestAction::make('reaccionSistemica')->table($aplicacion), data: [
                'grado' => GradoWao::Dos->value,
                'minutos_desde_aplicacion' => 15,
                'sintomas' => 'Urticaria generalizada y prurito.',
                'epinefrina' => false,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(GradoWao::Dos, $this->inmunoterapia->ultimaReaccionSistemica()?->grado);
        $this->assertStringEndsWith('el 23/11/2026', $this->inmunoterapia->ultimaReaccionSistemica()->resumen());

        $this->gestorDeAplicaciones()
            ->assertActionHidden(TestAction::make('reaccionSistemica')->table($aplicacion))
            ->assertSee('Grado 2');
    }

    public function test_calcula_el_atraso_y_muestra_la_regla_que_corresponde(): void
    {
        ReglaAtraso::factory()->create(['dias_desde' => 1, 'dias_hasta' => 6, 'accion' => 'Repetir la dosis anterior.']);
        ReglaAtraso::factory()->create(['dias_desde' => 7, 'dias_hasta' => 14, 'accion' => 'Reducir la dosis un 25 %.']);

        // La última dosis fue hace 20 días y el intervalo es semanal: le tocaba hace 13 días.
        $this->aplicar(['aplicada_en' => now()->subDays(20)]);

        $this->assertSame(13, $this->inmunoterapia->diasDeAtraso());
        $this->assertSame('Reducir la dosis un 25 %.', $this->inmunoterapia->reglaDeAtraso()?->accion);
    }

    public function test_sin_atraso_no_hay_regla(): void
    {
        ReglaAtraso::factory()->create(['dias_desde' => 1, 'dias_hasta' => 6]);

        $this->aplicar(['aplicada_en' => now()->subDays(3)]);

        $this->assertLessThanOrEqual(0, $this->inmunoterapia->diasDeAtraso());
        $this->assertNull($this->inmunoterapia->reglaDeAtraso());
    }

    /**
     * Aplica el siguiente paso del esquema con el frasco de su dilución.
     *
     * @param  array<string, mixed>  $datos
     */
    private function aplicar(array $datos = []): Aplicacion
    {
        /** @var EsquemaDosis $paso */
        $paso = $this->inmunoterapia->dosisEsperada()['esquema'];

        return $this->inmunoterapia->aplicaciones()->create([
            'frasco_id' => $this->inmunoterapia->frascos()->where('dilucion', $paso->dilucion)->value('id'),
            'esquema_dosis_id' => $paso->getKey(),
            'volumen_ml' => $paso->volumen_ml,
            'brazo' => 'izquierdo',
            ...$datos,
        ]);
    }

    private function gestorDeAplicaciones(): Testable
    {
        return Livewire::test(AplicacionesRelationManager::class, [
            'ownerRecord' => $this->inmunoterapia->refresh(),
            'pageClass' => ViewInmunoterapia::class,
        ]);
    }
}
