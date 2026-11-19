<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoCita: string implements HasColor, HasLabel
{
    case PrimeraConsulta = 'primera_consulta';
    case Control = 'control';
    case PrickTest = 'prick_test';
    case Aplicacion = 'aplicacion';

    public function getLabel(): string
    {
        return match ($this) {
            self::PrimeraConsulta => 'Primera consulta',
            self::Control => 'Control',
            self::PrickTest => 'Prick test',
            self::Aplicacion => 'Aplicación de vacuna',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PrimeraConsulta => 'primary',
            self::Control => 'info',
            self::PrickTest => 'warning',
            self::Aplicacion => 'success',
        };
    }

    /**
     * Duración sugerida. La aplicación incluye los 30 minutos de observación.
     */
    public function duracionMinutos(): int
    {
        return match ($this) {
            self::PrimeraConsulta => 60,
            self::Control => 30,
            self::PrickTest => 45,
            self::Aplicacion => 40,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
