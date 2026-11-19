<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CriticidadAlergia: string implements HasColor, HasLabel
{
    case Baja = 'baja';
    case Alta = 'alta';
    case NoEvaluable = 'no_evaluable';

    public function getLabel(): string
    {
        return match ($this) {
            self::Baja => 'Baja',
            self::Alta => 'Alta',
            self::NoEvaluable => 'No evaluable',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Baja => 'warning',
            self::Alta => 'danger',
            self::NoEvaluable => 'gray',
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
