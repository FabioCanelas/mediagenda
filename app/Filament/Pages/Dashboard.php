<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AplicacionesDeHoy;
use App\Filament\Widgets\CitasDeHoy;
use App\Filament\Widgets\PacientesAtrasados;
use App\Filament\Widgets\PreconsultasPorRevisar;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * HU-15: lo que la doctora tiene en el día al abrir el sistema.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Panel de inicio';

    protected static ?string $navigationLabel = 'Inicio';

    public function getWidgets(): array
    {
        return [
            CitasDeHoy::class,
            PreconsultasPorRevisar::class,
            AplicacionesDeHoy::class,
            PacientesAtrasados::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
