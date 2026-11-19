<?php

namespace App\Filament\Resources\Inmunoterapias\Pages;

use App\Filament\Resources\Inmunoterapias\InmunoterapiaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInmunoterapias extends ListRecords
{
    protected static string $resource = InmunoterapiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
