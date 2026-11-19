<?php

namespace App\Filament\Resources\Pacientes\Tables;

use App\Models\Paciente;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PacientesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('apellido_paterno')
            ->searchPlaceholder('Buscar por nombre o CI')
            ->columns([
                TextColumn::make('ci_completo')
                    ->label('CI')
                    ->searchable(['ci'])
                    ->sortable(['ci']),
                TextColumn::make('nombre_completo')
                    ->label('Paciente')
                    ->searchable(['nombres', 'apellido_paterno', 'apellido_materno'])
                    ->sortable(['apellido_paterno', 'apellido_materno', 'nombres'])
                    ->weight('medium'),
                TextColumn::make('edad')
                    ->label('Edad')
                    ->suffix(' años')
                    ->sortable(['fecha_nacimiento'], fn ($query, string $direction) => $query->orderBy('fecha_nacimiento', $direction === 'asc' ? 'desc' : 'asc')),
                TextColumn::make('celular')
                    ->label('Celular')
                    ->placeholder('—'),
                TextColumn::make('alergias_vigentes_count')
                    ->label('Alergias')
                    ->counts('alergiasVigentes')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->visible(fn (): bool => Auth::user()?->esDoctora() ?? false),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->visible(fn (): bool => Auth::user()?->esDoctora() ?? false),
            ])
            ->recordUrl(fn (Paciente $record): string => route('filament.admin.resources.pacientes.view', $record))
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
