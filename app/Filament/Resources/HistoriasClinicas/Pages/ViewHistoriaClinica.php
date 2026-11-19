<?php

namespace App\Filament\Resources\HistoriasClinicas\Pages;

use App\Filament\Resources\HistoriasClinicas\HistoriaClinicaResource;
use App\Filament\Widgets\AvisoAlergias;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHistoriaClinica extends ViewRecord
{
    protected static string $resource = HistoriaClinicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditHistoriaClinica::accionHistorialCambios(),
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AvisoAlergias::class,
        ];
    }
}
