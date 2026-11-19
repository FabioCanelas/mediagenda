<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Diluciones del extracto (volumen/volumen) con el código de colores de los
 * frascos que usan las guías de EE. UU.: del más diluido (plata) al
 * concentrado de mantenimiento (rojo).
 */
enum Dilucion: string implements HasColor, HasLabel
{
    case DiezMil = '1:10000';
    case Mil = '1:1000';
    case Cien = '1:100';
    case Diez = '1:10';
    case Concentrado = '1:1';

    public function color(): ColorFrasco
    {
        return match ($this) {
            self::DiezMil => ColorFrasco::Plata,
            self::Mil => ColorFrasco::Verde,
            self::Cien => ColorFrasco::Azul,
            self::Diez => ColorFrasco::Amarillo,
            self::Concentrado => ColorFrasco::Rojo,
        };
    }

    /**
     * Posición del más diluido (1) al concentrado (5).
     */
    public function orden(): int
    {
        return match ($this) {
            self::DiezMil => 1,
            self::Mil => 2,
            self::Cien => 3,
            self::Diez => 4,
            self::Concentrado => 5,
        };
    }

    public function getLabel(): string
    {
        return "{$this->value} · {$this->color()->getLabel()}";
    }

    public function getColor(): string
    {
        return $this->color()->getColor();
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
