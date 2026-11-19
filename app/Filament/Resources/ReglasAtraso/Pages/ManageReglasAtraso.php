<?php

namespace App\Filament\Resources\ReglasAtraso\Pages;

use App\Filament\Resources\ReglasAtraso\ReglaAtrasoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageReglasAtraso extends ManageRecords
{
    protected static string $resource = ReglaAtrasoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
