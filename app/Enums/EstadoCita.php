<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoCita: string implements HasColor, HasLabel
{
    case Programada = 'programada';
    case Confirmada = 'confirmada';
    case Atendida = 'atendida';
    case NoAsistio = 'no_asistio';
    case Cancelada = 'cancelada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Programada => 'Programada',
            self::Confirmada => 'Confirmada',
            self::Atendida => 'Atendida',
            self::NoAsistio => 'No asistió',
            self::Cancelada => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Programada => 'gray',
            self::Confirmada => 'info',
            self::Atendida => 'success',
            self::NoAsistio => 'warning',
            self::Cancelada => 'danger',
        };
    }

    /**
     * Valores aceptados, para las restricciones CHECK de la base de datos.
     *
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
