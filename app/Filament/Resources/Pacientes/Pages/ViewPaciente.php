<?php

namespace App\Filament\Resources\Pacientes\Pages;

use App\Filament\Resources\HistoriasClinicas\HistoriaClinicaResource;
use App\Filament\Resources\Pacientes\AccionesPreconsulta;
use App\Filament\Resources\Pacientes\PacienteResource;
use App\Filament\Resources\PruebasCutaneas\PruebaCutaneaResource;
use App\Filament\Widgets\AvisoAlergias;
use App\Filament\Widgets\EvolucionAct;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Models\PruebaCutanea;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

/**
 * @property Paciente $record
 */
class ViewPaciente extends ViewRecord
{
    protected static string $resource = PacienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('historiaClinica')
                ->label(fn (): string => $this->record->historiaClinica ? 'Historia clínica' : 'Crear historia clínica')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->visible(fn (): bool => Gate::allows('viewAny', HistoriaClinica::class))
                ->url(fn (): string => $this->record->historiaClinica
                    ? HistoriaClinicaResource::getUrl('edit', ['record' => $this->record->historiaClinica])
                    : HistoriaClinicaResource::getUrl('create', ['paciente' => $this->record->getKey()])),
            Action::make('nuevoPrickTest')
                ->label('Nuevo prick test')
                ->icon(Heroicon::OutlinedBeaker)
                ->color('gray')
                ->visible(fn (): bool => Gate::allows('create', PruebaCutanea::class))
                ->url(fn (): string => PruebaCutaneaResource::getUrl('create', ['paciente' => $this->record->getKey()])),
            AccionesPreconsulta::revisar(fn (Paciente $record): Paciente => $record),
            AccionesPreconsulta::prepararTablet(fn (Paciente $record): Paciente => $record),
            EditAction::make()
                ->color('gray'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AvisoAlergias::class,
        ];
    }

    /**
     * El gráfico del ACT aparece cuando el paciente tiene al menos un cuestionario.
     */
    protected function getFooterWidgets(): array
    {
        return $this->record->cuestionariosAct()->exists() ? [EvolucionAct::class] : [];
    }
}
