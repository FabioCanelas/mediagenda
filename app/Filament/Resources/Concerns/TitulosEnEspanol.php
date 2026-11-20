<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Support\Str;

/**
 * Filament pone en mayúscula cada palabra de los títulos ("Historia Clínica").
 * En español solo va con mayúscula la primera: "Historia clínica".
 */
trait TitulosEnEspanol
{
    public static function getTitleCaseModelLabel(): string
    {
        return Str::ucfirst(static::getModelLabel());
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return Str::ucfirst(static::getPluralModelLabel());
    }
}
