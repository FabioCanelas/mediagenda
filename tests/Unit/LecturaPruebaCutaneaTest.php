<?php

namespace Tests\Unit;

use App\Models\PruebaCutanea;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * HU-07: reglas de lectura del prick test.
 */
class LecturaPruebaCutaneaTest extends TestCase
{
    private function prueba(float $histamina, float $salina, float $criterio = 3.0): PruebaCutanea
    {
        return new PruebaCutanea([
            'control_positivo_mm' => $histamina,
            'control_negativo_mm' => $salina,
            'criterio_positividad_mm' => $criterio,
        ]);
    }

    /**
     * @return array<string, array{float, bool}>
     */
    public static function papulas(): array
    {
        return [
            'sin reacción' => [0.0, false],
            'justo debajo del criterio' => [2.5, false],
            'justo en el criterio' => [3.0, true],
            'reacción fuerte' => [8.0, true],
        ];
    }

    #[DataProvider('papulas')]
    public function test_es_positivo_desde_el_criterio_sobre_el_control_negativo(float $papula, bool $esperado): void
    {
        $this->assertSame($esperado, $this->prueba(histamina: 5, salina: 0)->esPositivo($papula));
    }

    public function test_el_criterio_se_mide_contra_el_control_negativo(): void
    {
        $prueba = $this->prueba(histamina: 6, salina: 1);

        $this->assertFalse($prueba->esPositivo(3.5));
        $this->assertTrue($prueba->esPositivo(4.0));
    }

    public function test_respeta_el_criterio_que_configura_la_doctora(): void
    {
        $prueba = $this->prueba(histamina: 6, salina: 0, criterio: 5);

        $this->assertFalse($prueba->esPositivo(4.0));
        $this->assertTrue($prueba->esPositivo(5.0));
    }

    public function test_si_la_histamina_no_reacciona_la_prueba_no_es_interpretable(): void
    {
        $prueba = $this->prueba(histamina: 1, salina: 0);

        $this->assertFalse($prueba->esInterpretable());
        $this->assertFalse($prueba->esPositivo(9.0));
        $this->assertStringContainsString('antihistamínicos', $prueba->motivoNoInterpretable());
    }

    public function test_si_la_solucion_salina_reacciona_la_prueba_no_es_interpretable(): void
    {
        $prueba = $this->prueba(histamina: 6, salina: 4);

        $this->assertFalse($prueba->esInterpretable());
        $this->assertStringContainsString('dermografismo', $prueba->motivoNoInterpretable());
    }

    public function test_una_prueba_valida_no_tiene_motivo_de_rechazo(): void
    {
        $this->assertNull($this->prueba(histamina: 5, salina: 0)->motivoNoInterpretable());
    }
}
