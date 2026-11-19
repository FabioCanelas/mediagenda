<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoAlergia: string implements HasLabel
{
    case Alergia = 'alergia';
    case Intolerancia = 'intolerancia';

    public function getLabel(): string
    {
        return match ($this) {
            self::Alergia => 'Alergia',
            self::Intolerancia => 'Intolerancia',
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
