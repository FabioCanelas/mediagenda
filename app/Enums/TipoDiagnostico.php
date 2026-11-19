<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoDiagnostico: string implements HasColor, HasLabel
{
    case Principal = 'principal';
    case Secundario = 'secundario';

    public function getLabel(): string
    {
        return match ($this) {
            self::Principal => 'Principal',
            self::Secundario => 'Secundario',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Principal => 'primary',
            self::Secundario => 'gray',
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
