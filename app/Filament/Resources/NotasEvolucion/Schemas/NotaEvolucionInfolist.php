<?php

namespace App\Filament\Resources\NotasEvolucion\Schemas;

use App\Models\NotaEvolucion;
use App\Models\NotaEvolucionAnexo;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NotaEvolucionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nota')
                    ->columns(4)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('paciente.nombre_completo')
                            ->label('Paciente')
                            ->weight('bold')
                            ->columnSpan(2),
                        TextEntry::make('fecha')
                            ->label('Fecha')
                            ->date('d/m/Y'),
                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge(),
                        TextEntry::make('cierre')
                            ->label('Cerrada')
                            ->state(fn (NotaEvolucion $record): ?string => $record->estaCerrada()
                                ? $record->cerrada_en->format('d/m/Y H:i').' por '.$record->cerradaPor?->name
                                : null)
                            ->placeholder('Todavía es un borrador')
                            ->columnSpanFull(),
                    ]),
                Section::make('Contenido')
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('evolucion')->label('Evolución y actualización del cuadro clínico'),
                        TextEntry::make('signos')
                            ->label('Signos vitales')
                            ->state(fn (NotaEvolucion $record): ?string => self::signosVitales($record))
                            ->placeholder('No se registraron'),
                        TextEntry::make('resultados_estudios')->label('Resultados de estudios')->placeholder('—'),
                        TextEntry::make('diagnosticos')->label('Diagnósticos')->placeholder('—'),
                        TextEntry::make('pronostico')->label('Pronóstico')->placeholder('—'),
                        TextEntry::make('tratamiento')->label('Tratamiento e indicaciones')->placeholder('—'),
                    ]),
                Section::make('Anexos de corrección')
                    ->description('Las correcciones de una nota cerrada se agregan aquí; la nota original no cambia.')
                    ->visible(fn (NotaEvolucion $record): bool => $record->estaCerrada())
                    ->columnSpanFull()
                    ->components([
                        RepeatableEntry::make('anexos')
                            ->hiddenLabel()
                            ->placeholder('Sin anexos')
                            ->components([
                                TextEntry::make('encabezado')
                                    ->hiddenLabel()
                                    ->weight('medium')
                                    ->state(fn (NotaEvolucionAnexo $record): string => $record->created_at->format('d/m/Y H:i').' · '.($record->autor?->name ?? 'Sistema')),
                                TextEntry::make('texto')->hiddenLabel(),
                            ]),
                    ]),
            ]);
    }

    private static function signosVitales(NotaEvolucion $nota): ?string
    {
        $partes = array_filter([
            $nota->presion_sistolica ? "PA {$nota->presion_sistolica}/{$nota->presion_diastolica} mmHg" : null,
            $nota->frecuencia_cardiaca ? "FC {$nota->frecuencia_cardiaca} lpm" : null,
            $nota->frecuencia_respiratoria ? "FR {$nota->frecuencia_respiratoria} rpm" : null,
            $nota->temperatura ? "T {$nota->temperatura} °C" : null,
            $nota->saturacion_oxigeno ? "SpO₂ {$nota->saturacion_oxigeno} %" : null,
            $nota->peso_kg ? "Peso {$nota->peso_kg} kg" : null,
        ]);

        return $partes === [] ? null : implode(' · ', $partes);
    }
}
