<?php

namespace App\Filament\Resources\Pacientes\Pages;

use App\Filament\Resources\Pacientes\PacienteResource;
use App\Filament\Widgets\AvisoAlergias;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPaciente extends EditRecord
{
    protected static string $resource = PacienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            // Borrado lógico: el paciente se oculta, pero sus datos se conservan.
            DeleteAction::make()
                ->label('Ocultar paciente')
                ->modalDescription('El paciente dejará de aparecer en la lista, pero su expediente se conserva y se puede restaurar.'),
            RestoreAction::make(),
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
