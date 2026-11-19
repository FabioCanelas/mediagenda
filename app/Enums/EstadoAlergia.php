<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoAlergia: string implements HasColor, HasLabel
{
    case Sospechada = 'sospechada';
    case Confirmada = 'confirmada';
    case Descartada = 'descartada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sospechada => 'Sospechada',
            self::Confirmada => 'Confirmada',
            self::Descartada => 'Descartada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Sospechada => 'warning',
            self::Confirmada => 'danger',
            self::Descartada => 'gray',
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
