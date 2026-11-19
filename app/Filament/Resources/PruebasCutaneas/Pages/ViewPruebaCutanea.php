<?php

namespace App\Filament\Resources\PruebasCutaneas\Pages;

use App\Filament\Resources\Pacientes\PacienteResource;
use App\Filament\Resources\PruebasCutaneas\PruebaCutaneaResource;
use App\Filament\Widgets\AvisoAlergias;
use App\Models\PruebaCutanea;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property PruebaCutanea $record
 */
class ViewPruebaCutanea extends ViewRecord
{
    protected static string $resource = PruebaCutaneaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verPaciente')
                ->label('Ficha del paciente')
                ->icon(Heroicon::OutlinedUser)
                ->color('gray')
                ->url(fn (): string => PacienteResource::getUrl('view', ['record' => $this->record->paciente_id])),
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
