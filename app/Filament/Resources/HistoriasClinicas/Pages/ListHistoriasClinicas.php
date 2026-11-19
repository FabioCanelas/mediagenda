<?php

namespace App\Filament\Resources\HistoriasClinicas\Pages;

use App\Filament\Resources\HistoriasClinicas\HistoriaClinicaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHistoriasClinicas extends ListRecords
{
    protected static string $resource = HistoriaClinicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
