<?php

namespace App\Filament\Resources\Citas\Pages;

use App\Filament\Resources\Citas\CitaResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCitas extends ListRecords
{
    protected static string $resource = CitaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('semana')
                ->label('Vista semanal')
                ->icon(Heroicon::OutlinedCalendar)
                ->color('gray')
                ->url(CitaResource::getUrl('semana')),
            CreateAction::make()
                ->label('Nueva cita'),
        ];
    }
}
