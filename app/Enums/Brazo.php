<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Brazo: string implements HasLabel
{
    case Izquierdo = 'izquierdo';
    case Derecho = 'derecho';

    public function getLabel(): string
    {
        return match ($this) {
            self::Izquierdo => 'Izquierdo',
            self::Derecho => 'Derecho',
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
