<?php

namespace App\Filament\Resources\NotasEvolucion\Pages;

use App\Filament\Resources\NotasEvolucion\NotaEvolucionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotasEvolucion extends ListRecords
{
    protected static string $resource = NotaEvolucionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
