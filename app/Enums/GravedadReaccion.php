<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum GravedadReaccion: string implements HasColor, HasLabel
{
    case Leve = 'leve';
    case Moderada = 'moderada';
    case Grave = 'grave';

    public function getLabel(): string
    {
        return match ($this) {
            self::Leve => 'Leve',
            self::Moderada => 'Moderada',
            self::Grave => 'Grave',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Leve => 'warning',
            self::Moderada => 'warning',
            self::Grave => 'danger',
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
