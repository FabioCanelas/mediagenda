<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoConsentimiento: string implements HasColor, HasLabel
{
    case Pendiente = 'pendiente';
    case Firmado = 'firmado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Falta la firma',
            self::Firmado => 'Firmado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Firmado => 'success',
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
