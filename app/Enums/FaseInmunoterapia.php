<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FaseInmunoterapia: string implements HasColor, HasLabel
{
    case Inicio = 'inicio';
    case Mantenimiento = 'mantenimiento';

    public function getLabel(): string
    {
        return match ($this) {
            self::Inicio => 'Fase de inicio',
            self::Mantenimiento => 'Mantenimiento',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Inicio => 'info',
            self::Mantenimiento => 'success',
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
