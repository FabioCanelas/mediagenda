<?php

namespace App\Filament\Resources\HistoriasClinicas\Pages;

use App\Filament\Resources\HistoriasClinicas\HistoriaClinicaResource;
use App\Filament\Widgets\AvisoAlergias;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use Filament\Resources\Pages\CreateRecord;

class CreateHistoriaClinica extends CreateRecord
{
    protected static string $resource = HistoriaClinicaResource::class;

    public ?Paciente $paciente = null;

    /**
     * Si el paciente ya tiene historia clínica, se abre esa en lugar de crear otra.
     */
    public function mount(): void
    {
        $pacienteId = request()->query('paciente');

        if (is_string($pacienteId)) {
            $existente = HistoriaClinica::where('paciente_id', $pacienteId)->first();

            if ($existente !== null) {
                $this->redirect(HistoriaClinicaResource::getUrl('edit', ['record' => $existente]));

                return;
            }

            $this->paciente = Paciente::find($pacienteId);
        }

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
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
