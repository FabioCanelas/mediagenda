<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoInmunoterapia: string implements HasColor, HasLabel
{
    case Activa = 'activa';
    case Suspendida = 'suspendida';
    case Finalizada = 'finalizada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Activa => 'Activa',
            self::Suspendida => 'Suspendida',
            self::Finalizada => 'Finalizada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Activa => 'success',
            self::Suspendida => 'warning',
            self::Finalizada => 'gray',
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
