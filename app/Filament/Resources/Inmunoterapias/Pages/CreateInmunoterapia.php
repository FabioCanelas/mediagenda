<?php

namespace App\Filament\Resources\Inmunoterapias\Pages;

use App\Filament\Resources\Inmunoterapias\InmunoterapiaResource;
use App\Filament\Widgets\AvisoAlergias;
use App\Models\Paciente;
use Filament\Resources\Pages\CreateRecord;

class CreateInmunoterapia extends CreateRecord
{
    protected static string $resource = InmunoterapiaResource::class;

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
