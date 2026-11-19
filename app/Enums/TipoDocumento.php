<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoDocumento: string implements HasLabel
{
    case Receta = 'receta';
    case Consentimiento = 'consentimiento';

    public function getLabel(): string
    {
        return match ($this) {
            self::Receta => 'Receta',
            self::Consentimiento => 'Consentimiento informado',
        };
    }

    /**
     * Prefijo del folio: R-2026-000001, C-2026-000001.
     */
    public function prefijo(): string
    {
        return match ($this) {
            self::Receta => 'R',
            self::Consentimiento => 'C',
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
