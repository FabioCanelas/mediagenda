<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum GrupoAlergeno: string implements HasLabel
{
    case Acaros = 'acaros';
    case Arboles = 'arboles';
    case Pastos = 'pastos';
    case Malezas = 'malezas';
    case Epitelios = 'epitelios';
    case Hongos = 'hongos';
    case Insectos = 'insectos';
    case Alimentos = 'alimentos';
    case Otros = 'otros';

    public function getLabel(): string
    {
        return match ($this) {
            self::Acaros => 'Ácaros',
            self::Arboles => 'Pólenes de árboles',
            self::Pastos => 'Pólenes de pastos',
            self::Malezas => 'Pólenes de malezas',
            self::Epitelios => 'Epitelios de animales',
            self::Hongos => 'Hongos',
            self::Insectos => 'Insectos',
            self::Alimentos => 'Alimentos',
            self::Otros => 'Otros',
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
