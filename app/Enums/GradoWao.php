<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Grados de la escala de reacciones sistémicas de la Organización Mundial
 * de Alergia (WAO). Las descripciones son un resumen para elegir el grado;
 * los criterios completos están en la guía.
 */
enum GradoWao: int implements HasColor, HasLabel
{
    case Uno = 1;
    case Dos = 2;
    case Tres = 3;
    case Cuatro = 4;
    case Cinco = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::Uno => 'Grado 1 · síntomas en un solo órgano',
            self::Dos => 'Grado 2 · más de un órgano o asma leve',
            self::Tres => 'Grado 3 · asma grave o edema laríngeo',
            self::Cuatro => 'Grado 4 · insuficiencia respiratoria o hipotensión',
            self::Cinco => 'Grado 5 · muerte',
        };
    }

    public function getColor(): string
    {
        return $this->value >= 3 ? 'danger' : 'warning';
    }

    /**
     * @return array<int, int>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
