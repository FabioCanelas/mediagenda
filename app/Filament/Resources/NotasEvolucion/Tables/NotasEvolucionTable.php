<?php

namespace App\Filament\Resources\NotasEvolucion\Tables;

use App\Enums\EstadoNota;
use App\Models\NotaEvolucion;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotasEvolucionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('paciente')->withCount('anexos'))
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('paciente')
                    ->label('Paciente')
                    ->state(fn (NotaEvolucion $record): string => $record->paciente->nombre_completo)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'paciente',
                        fn (Builder $paciente) => $paciente
                            ->whereLike('nombres', "%{$search}%")
                            ->orWhereLike('apellido_paterno', "%{$search}%")
                            ->orWhereLike('apellido_materno', "%{$search}%")
                            ->orWhereLike('ci', "{$search}%"),
                    ))
                    ->weight('medium'),
                TextColumn::make('evolucion')
                    ->label('Evolución')
                    ->limit(60),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('anexos_count')
                    ->label('Anexos')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(EstadoNota::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
