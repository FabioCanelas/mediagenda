<?php

namespace App\Filament\Resources\Alergenos\Pages;

use App\Filament\Resources\Alergenos\AlergenoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAlergenos extends ManageRecords
{
    protected static string $resource = AlergenoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
