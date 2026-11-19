<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CategoriaAlergia: string implements HasLabel
{
    case Medicamento = 'medicamento';
    case Alimento = 'alimento';
    case Ambiental = 'ambiental';
    case Biologico = 'biologico';
    case Otro = 'otro';

    public function getLabel(): string
    {
        return match ($this) {
            self::Medicamento => 'Medicamento',
            self::Alimento => 'Alimento',
            self::Ambiental => 'Ambiental',
            self::Biologico => 'Biológico',
            self::Otro => 'Otro',
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
