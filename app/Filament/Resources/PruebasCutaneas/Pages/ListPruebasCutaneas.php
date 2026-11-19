<?php

namespace App\Filament\Resources\PruebasCutaneas\Pages;

use App\Filament\Resources\PruebasCutaneas\PruebaCutaneaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPruebasCutaneas extends ListRecords
{
    protected static string $resource = PruebaCutaneaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
