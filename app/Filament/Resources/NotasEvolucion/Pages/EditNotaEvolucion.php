<?php

namespace App\Filament\Resources\NotasEvolucion\Pages;

use App\Filament\Resources\NotasEvolucion\AccionesNota;
use App\Filament\Resources\NotasEvolucion\NotaEvolucionResource;
use App\Filament\Widgets\AvisoAlergias;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditNotaEvolucion extends EditRecord
{
    protected static string $resource = NotaEvolucionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            AccionesNota::cerrar(),
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
