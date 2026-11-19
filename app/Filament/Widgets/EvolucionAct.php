<?php

namespace App\Filament\Widgets;

use App\Enums\ControlAsma;
use App\Models\CuestionarioAct;
use App\Models\Paciente;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

/**
 * HU-16: gráfico del puntaje ACT en el tiempo, con las líneas de asma
 * controlada (20) y parcialmente controlada (16).
 */
class EvolucionAct extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Evolución del control del asma (ACT)';

    protected ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 'full';

    public ?Paciente $record = null;

    public static function canView(): bool
    {
        return Auth::user()?->esDoctora() ?? false;
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $cuestionarios = $this->record?->cuestionariosAct()->orderBy('fecha')->get() ?? collect();
        $total = $cuestionarios->count();

        return [
            'labels' => $cuestionarios->map(fn (CuestionarioAct $cuestionario): string => $cuestionario->fecha->format('d/m/Y'))->all(),
            'datasets' => [
                [
                    'label' => 'Puntaje',
                    'data' => $cuestionarios->map(fn (CuestionarioAct $cuestionario): int => $cuestionario->puntajeTotal())->all(),
                    'borderColor' => '#0d9488',
                    'backgroundColor' => '#0d9488',
                    'tension' => 0.2,
                ],
                [
                    'label' => 'Controlada (20 o más)',
                    'data' => array_fill(0, $total, ControlAsma::PUNTAJE_CONTROLADA),
                    'borderColor' => '#16a34a',
                    'borderDash' => [6, 4],
                    'pointRadius' => 0,
                    'fill' => false,
                ],
                [
                    'label' => 'Parcial (16 a 19)',
                    'data' => array_fill(0, $total, ControlAsma::PUNTAJE_PARCIAL),
                    'borderColor' => '#d97706',
                    'borderDash' => [6, 4],
                    'pointRadius' => 0,
                    'fill' => false,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['min' => 5, 'max' => 25, 'ticks' => ['stepSize' => 5]],
            ],
        ];
    }
}
