<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReaccionLocal: string implements HasColor, HasLabel
{
    case Ninguna = 'ninguna';
    case Leve = 'leve';
    case Moderada = 'moderada';
    case Grande = 'grande';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ninguna => 'Ninguna',
            self::Leve => 'Leve',
            self::Moderada => 'Moderada',
            self::Grande => 'Grande',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ninguna => 'gray',
            self::Leve => 'warning',
            self::Moderada => 'warning',
            self::Grande => 'danger',
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
