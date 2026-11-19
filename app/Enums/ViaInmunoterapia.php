<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ViaInmunoterapia: string implements HasLabel
{
    case Subcutanea = 'subcutanea';
    case Sublingual = 'sublingual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Subcutanea => 'Subcutánea',
            self::Sublingual => 'Sublingual',
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
