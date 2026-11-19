<?php

namespace App\Filament\Resources\PruebasCutaneas\Pages;

use App\Filament\Resources\PruebasCutaneas\PruebaCutaneaResource;
use App\Filament\Widgets\AvisoAlergias;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPruebaCutanea extends EditRecord
{
    protected static string $resource = PruebaCutaneaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AvisoAlergias::class,
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
