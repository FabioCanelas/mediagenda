<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Departamento: string implements HasLabel
{
    case LaPaz = 'LP';
    case Cochabamba = 'CB';
    case SantaCruz = 'SC';
    case Oruro = 'OR';
    case Potosi = 'PT';
    case Chuquisaca = 'CH';
    case Tarija = 'TJ';
    case Beni = 'BE';
    case Pando = 'PD';

    public function getLabel(): string
    {
        return match ($this) {
            self::LaPaz => 'La Paz',
            self::Cochabamba => 'Cochabamba',
            self::SantaCruz => 'Santa Cruz',
            self::Oruro => 'Oruro',
            self::Potosi => 'Potosí',
            self::Chuquisaca => 'Chuquisaca',
            self::Tarija => 'Tarija',
            self::Beni => 'Beni',
            self::Pando => 'Pando',
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
