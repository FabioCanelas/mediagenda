<?php

namespace App\Filament\Resources\Citas\Pages;

use App\Filament\Resources\Citas\CitaResource;
use App\Support\BaseDeDatos\ErrorDeBase;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class CreateCita extends CreateRecord
{
    protected static string $resource = CitaResource::class;

    /**
     * Si dos personas agendan el mismo horario a la vez, el trigger de la base
     * rechaza la segunda cita y aquí se avisa en lugar de mostrar un error.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (QueryException $error) {
            Notification::make()
                ->title('No se pudo agendar')
                ->body(ErrorDeBase::mensaje($error, 'La base de datos rechazó la cita.'))
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
