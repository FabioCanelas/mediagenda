<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Rol: string implements HasColor, HasLabel
{
    case Doctora = 'doctora';
    case Recepcion = 'recepcion';

    public function getLabel(): string
    {
        return match ($this) {
            self::Doctora => 'Doctora',
            self::Recepcion => 'Recepción',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Doctora => 'primary',
            self::Recepcion => 'gray',
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
