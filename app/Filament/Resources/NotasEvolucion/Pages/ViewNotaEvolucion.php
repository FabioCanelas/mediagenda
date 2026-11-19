<?php

namespace App\Filament\Resources\NotasEvolucion\Pages;

use App\Filament\Resources\NotasEvolucion\AccionesNota;
use App\Filament\Resources\NotasEvolucion\NotaEvolucionResource;
use App\Filament\Resources\Pacientes\PacienteResource;
use App\Filament\Widgets\AvisoAlergias;
use App\Models\NotaEvolucion;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property NotaEvolucion $record
 */
class ViewNotaEvolucion extends ViewRecord
{
    protected static string $resource = NotaEvolucionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verPaciente')
                ->label('Ficha del paciente')
                ->icon(Heroicon::OutlinedUser)
                ->color('gray')
                ->url(fn (): string => PacienteResource::getUrl('view', ['record' => $this->record->paciente_id])),
            EditAction::make(),
            AccionesNota::cerrar(),
            AccionesNota::agregarAnexo(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AvisoAlergias::class,
        ];
    }
}
