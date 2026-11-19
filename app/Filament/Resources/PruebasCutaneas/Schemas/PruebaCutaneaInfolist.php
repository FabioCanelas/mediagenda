<?php

namespace App\Filament\Resources\PruebasCutaneas\Schemas;

use App\Models\PruebaCutanea;
use App\Models\PruebaCutaneaResultado;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;

class PruebaCutaneaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Prueba')
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
                        TextEntry::make('realizadoPor.name')
                            ->label('Realizó')
                            ->placeholder('—'),
                        IconEntry::make('antihistaminicos_suspendidos')
                            ->label('Suspendió antihistamínicos')
                            ->boolean(),
                        TextEntry::make('minutos_lectura')
                            ->label('Lectura')
                            ->suffix(' minutos'),
                        TextEntry::make('criterio_positividad_mm')
                            ->label('Criterio de positividad')
                            ->suffix(' mm'),
                    ]),
                Section::make('Controles')
                    ->columns(3)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('control_positivo_mm')
                            ->label('Histamina')
                            ->suffix(' mm'),
                        TextEntry::make('control_negativo_mm')
                            ->label('Solución salina')
                            ->suffix(' mm'),
                        TextEntry::make('interpretable')
                            ->label('Resultado de la prueba')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Interpretable' : 'No interpretable')
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                        TextEntry::make('motivo')
                            ->label('Motivo')
                            ->state(fn (PruebaCutanea $record): ?string => $record->motivoNoInterpretable())
                            ->color('danger')
                            ->visible(fn (PruebaCutanea $record): bool => ! $record->interpretable)
                            ->columnSpanFull(),
                    ]),
                Section::make('Resultados por alérgeno')
                    ->columnSpanFull()
                    ->components([
                        RepeatableEntry::make('resultados')
                            ->hiddenLabel()
                            ->state(fn (PruebaCutanea $record): Collection => $record->resultados()
                                ->with('alergeno')
                                ->orderByDesc('positivo')
                                ->orderByDesc('papula_mm')
                                ->get())
                            ->columns(4)
                            ->components([
                                TextEntry::make('alergeno.nombre')
                                    ->label('Alérgeno')
                                    ->weight('medium'),
                                TextEntry::make('papula_mm')
                                    ->label('Pápula')
                                    ->suffix(' mm'),
                                TextEntry::make('eritema_mm')
                                    ->label('Eritema')
                                    ->suffix(' mm')
                                    ->placeholder('—'),
                                TextEntry::make('positivo')
                                    ->label('Resultado')
                                    ->badge()
                                    ->state(fn (PruebaCutaneaResultado $record): string => $record->positivo ? 'Positivo' : 'Negativo')
                                    ->color(fn (string $state): string => $state === 'Positivo' ? 'danger' : 'gray'),
                            ]),
                    ]),
                TextEntry::make('observaciones')
                    ->label('Observaciones')
                    ->placeholder('—')
                    ->columnSpanFull(),
            ]);
    }
}
