<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoPreconsulta: string implements HasColor, HasLabel
{
    case Pendiente = 'pendiente';
    case Enviada = 'enviada';
    case Revisada = 'revisada';
    case Descartada = 'descartada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Esperando al paciente',
            self::Enviada => 'Por revisar',
            self::Revisada => 'Pasada a la historia',
            self::Descartada => 'Descartada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'gray',
            self::Enviada => 'warning',
            self::Revisada => 'success',
            self::Descartada => 'danger',
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
