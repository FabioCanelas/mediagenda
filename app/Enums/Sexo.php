<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Sexo: string implements HasLabel
{
    case Femenino = 'F';
    case Masculino = 'M';

    public function getLabel(): string
    {
        return match ($this) {
            self::Femenino => 'Femenino',
            self::Masculino => 'Masculino',
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
