<?php

namespace App\Filament\Resources\Pacientes\Pages;

use App\Filament\Resources\Pacientes\PacienteResource;
use App\Models\Paciente;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePaciente extends CreateRecord
{
    protected static string $resource = PacienteResource::class;

    /**
     * HU-02, escenario 2: si el CI ya existe, se avisa y se ofrece abrir ese expediente
     * (aunque el paciente esté oculto por borrado lógico).
     */
    protected function beforeCreate(): void
    {
        $datos = $this->form->getState();

        $existente = Paciente::withTrashed()
            ->where('ci', $datos['ci'])
            ->where('ci_complemento', $datos['ci_complemento'] ?? '')
            ->first();

        if ($existente === null) {
            return;
        }

        Notification::make()
            ->title('Este paciente ya está registrado')
            ->body("El CI {$existente->ci_completo} pertenece a {$existente->nombre_completo}.")
            ->warning()
            ->persistent()
            ->actions([
                Action::make('verExpediente')
                    ->label('Ver su expediente')
                    ->url(PacienteResource::getUrl('view', ['record' => $existente])),
            ])
            ->send();

        $this->halt();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
