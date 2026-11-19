<?php

namespace App\Filament\Resources\NotasEvolucion\Pages;

use App\Filament\Resources\NotasEvolucion\NotaEvolucionResource;
use App\Filament\Widgets\AvisoAlergias;
use App\Models\Paciente;
use Filament\Resources\Pages\CreateRecord;

class CreateNotaEvolucion extends CreateRecord
{
    protected static string $resource = NotaEvolucionResource::class;

    public ?Paciente $paciente = null;

    public function mount(): void
    {
        $pacienteId = request()->query('paciente');
        $this->paciente = is_string($pacienteId) ? Paciente::find($pacienteId) : null;

        parent::mount();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AvisoAlergias::make(['record' => $this->paciente]),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
