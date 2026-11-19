<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Interpretación del puntaje del cuestionario de control del asma (ACT).
 */
enum ControlAsma: string implements HasColor, HasLabel
{
    case Controlada = 'controlada';
    case ParcialmenteControlada = 'parcialmente_controlada';
    case NoControlada = 'no_controlada';

    public const PUNTAJE_CONTROLADA = 20;

    public const PUNTAJE_PARCIAL = 16;

    public static function segunPuntaje(int $puntaje): self
    {
        return match (true) {
            $puntaje >= self::PUNTAJE_CONTROLADA => self::Controlada,
            $puntaje >= self::PUNTAJE_PARCIAL => self::ParcialmenteControlada,
            default => self::NoControlada,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Controlada => 'Asma controlada',
            self::ParcialmenteControlada => 'Parcialmente controlada',
            self::NoControlada => 'No controlada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Controlada => 'success',
            self::ParcialmenteControlada => 'warning',
            self::NoControlada => 'danger',
        };
    }
}
