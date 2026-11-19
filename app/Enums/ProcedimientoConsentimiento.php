<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProcedimientoConsentimiento: string implements HasLabel
{
    case PrickTest = 'prick_test';
    case Inmunoterapia = 'inmunoterapia';

    public function getLabel(): string
    {
        return match ($this) {
            self::PrickTest => 'Pruebas cutáneas (prick test)',
            self::Inmunoterapia => 'Inmunoterapia con alérgenos',
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
