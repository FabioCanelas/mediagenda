<?php

namespace App\Filament\Resources\HistoriasClinicas\Schemas;

use App\Models\HistoriaClinica;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HistoriaClinicaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('paciente.nombre_completo')
                    ->label('Paciente')
                    ->weight('bold')
                    ->columnSpanFull(),
                Section::make('Interrogatorio')
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('padecimiento_actual')->label('Padecimiento actual')->placeholder('—'),
                        TextEntry::make('antecedentes_heredofamiliares')->label('Antecedentes heredofamiliares')->placeholder('—'),
                        TextEntry::make('antecedentes_personales_patologicos')->label('Antecedentes personales patológicos')->placeholder('—'),
                        TextEntry::make('antecedentes_personales_no_patologicos')->label('Antecedentes personales no patológicos')->placeholder('—'),
                        TextEntry::make('interrogatorio_aparatos_sistemas')->label('Revisión de otros síntomas, sistema por sistema')->placeholder('—'),
                    ]),
                Section::make('Exploración física')
                    ->columns(4)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('presion')
                            ->label('Presión arterial')
                            ->state(fn (HistoriaClinica $record): ?string => $record->presion_sistolica
                                ? "{$record->presion_sistolica}/{$record->presion_diastolica} mmHg"
                                : null)
                            ->placeholder('—'),
                        TextEntry::make('frecuencia_cardiaca')->label('Frecuencia cardiaca')->suffix(' lpm')->placeholder('—'),
                        TextEntry::make('frecuencia_respiratoria')->label('Frecuencia respiratoria')->suffix(' rpm')->placeholder('—'),
                        TextEntry::make('temperatura')->label('Temperatura')->suffix(' °C')->placeholder('—'),
                        TextEntry::make('saturacion_oxigeno')->label('Saturación de O₂')->suffix(' %')->placeholder('—'),
                        TextEntry::make('peso_kg')->label('Peso')->suffix(' kg')->placeholder('—'),
                        TextEntry::make('talla_cm')->label('Talla')->suffix(' cm')->placeholder('—'),
                        TextEntry::make('imc')
                            ->label('IMC')
                            ->state(fn (HistoriaClinica $record): ?float => $record->imc())
                            ->placeholder('—'),
                        TextEntry::make('habitus_exterior')->label('Habitus exterior')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('exploracion_regional')->label('Exploración por regiones')->placeholder('—')->columnSpanFull(),
                    ]),
                Section::make('Diagnóstico y plan')
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('resultados_estudios')->label('Resultados de estudios')->placeholder('—'),
                        RepeatableEntry::make('diagnosticos')
                            ->label('Diagnósticos')
                            ->columns(3)
                            ->components([
                                TextEntry::make('cie10_codigo')->label('CIE-10')->placeholder('Sin código')->badge(),
                                TextEntry::make('cie10.descripcion')->label('Diagnóstico')
                                    ->state(fn ($record): string => $record->cie10?->descripcion ?? $record->descripcion),
                                TextEntry::make('tipo')->label('Tipo')->badge(),
                            ]),
                        TextEntry::make('pronostico')->label('Pronóstico')->placeholder('—'),
                        TextEntry::make('indicacion_terapeutica')->label('Indicación terapéutica')->placeholder('—'),
                    ]),
            ]);
    }
}
