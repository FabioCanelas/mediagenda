<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ColorFrasco: string implements HasColor, HasLabel
{
    case Plata = 'plata';
    case Verde = 'verde';
    case Azul = 'azul';
    case Amarillo = 'amarillo';
    case Rojo = 'rojo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Plata => 'Plata',
            self::Verde => 'Verde',
            self::Azul => 'Azul',
            self::Amarillo => 'Amarillo',
            self::Rojo => 'Rojo',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Plata => 'gray',
            self::Verde => 'success',
            self::Azul => 'info',
            self::Amarillo => 'warning',
            self::Rojo => 'danger',
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
