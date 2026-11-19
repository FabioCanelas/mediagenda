<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoNota: string implements HasColor, HasLabel
{
    case Borrador = 'borrador';
    case Cerrada = 'cerrada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Cerrada => 'Cerrada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Borrador => 'warning',
            self::Cerrada => 'success',
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
