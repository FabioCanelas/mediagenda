<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RespuestaSiNo: string implements HasLabel
{
    case Si = 'si';
    case No = 'no';
    case NoSe = 'no_se';

    public function getLabel(): string
    {
        return match ($this) {
            self::Si => 'Sí',
            self::No => 'No',
            self::NoSe => 'No sé',
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
