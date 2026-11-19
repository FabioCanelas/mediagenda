<?php

namespace App\Filament\Resources\HistoriasClinicas\Pages;

use App\Filament\Resources\HistoriasClinicas\HistoriaClinicaResource;
use App\Filament\Resources\Pacientes\AccionesPreconsulta;
use App\Filament\Resources\Pacientes\PacienteResource;
use App\Filament\Widgets\AvisoAlergias;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Models\RegistroAuditoria;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property HistoriaClinica $record
 */
class EditHistoriaClinica extends EditRecord
{
    protected static string $resource = HistoriaClinicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesPreconsulta::revisar(fn (HistoriaClinica $record): Paciente => $record->paciente),
            self::accionHistorialCambios(),
            Action::make('verPaciente')
                ->label('Ficha del paciente')
                ->icon(Heroicon::OutlinedUser)
                ->color('gray')
                ->url(fn (): string => PacienteResource::getUrl('view', ['record' => $this->record->paciente_id])),
            ViewAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AvisoAlergias::class,
        ];
    }

    /**
     * HU-03, escenario 2: cada versión anterior queda en la auditoría.
     */
    public static function accionHistorialCambios(): Action
    {
        return Action::make('historialCambios')
            ->label('Historial de cambios')
            ->icon(Heroicon::OutlinedClock)
            ->color('gray')
            ->modalHeading('Historial de cambios')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn (HistoriaClinica $record) => view('filament.historias-clinicas.historial-cambios', [
                'registros' => RegistroAuditoria::query()
                    ->with('usuario')
                    ->where('tabla', 'historias_clinicas')
                    ->where('registro_id', $record->getKey())
                    ->orderByDesc('realizado_en')
                    ->get(),
                'etiquetas' => self::etiquetasCampos(),
            ]));
    }

    /**
     * @return array<string, string>
     */
    public static function etiquetasCampos(): array
    {
        return [
            'antecedentes_heredofamiliares' => 'Antecedentes heredofamiliares',
            'antecedentes_personales_patologicos' => 'Antecedentes personales patológicos',
            'antecedentes_personales_no_patologicos' => 'Antecedentes personales no patológicos',
            'padecimiento_actual' => 'Padecimiento actual',
            'interrogatorio_aparatos_sistemas' => 'Interrogatorio por aparatos y sistemas',
            'habitus_exterior' => 'Habitus exterior',
            'presion_sistolica' => 'Presión sistólica',
            'presion_diastolica' => 'Presión diastólica',
            'frecuencia_cardiaca' => 'Frecuencia cardiaca',
            'frecuencia_respiratoria' => 'Frecuencia respiratoria',
            'temperatura' => 'Temperatura',
            'saturacion_oxigeno' => 'Saturación de oxígeno',
            'peso_kg' => 'Peso',
            'talla_cm' => 'Talla',
            'exploracion_regional' => 'Exploración por regiones',
            'resultados_estudios' => 'Resultados de estudios',
            'pronostico' => 'Pronóstico',
            'indicacion_terapeutica' => 'Indicación terapéutica',
        ];
    }
}
