<?php

namespace App\Filament\Resources\Inmunoterapias\Tables;

use App\Enums\EstadoInmunoterapia;
use App\Enums\FaseInmunoterapia;
use App\Models\Inmunoterapia;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InmunoterapiasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['paciente', 'proximaAplicacion']))
            ->defaultSort('fecha_inicio', 'desc')
            ->columns([
                TextColumn::make('paciente')
                    ->label('Paciente')
                    ->state(fn (Inmunoterapia $record): string => $record->paciente->nombre_completo)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'paciente',
                        fn (Builder $paciente) => $paciente
                            ->whereLike('nombres', "%{$search}%")
                            ->orWhereLike('apellido_paterno', "%{$search}%")
                            ->orWhereLike('apellido_materno', "%{$search}%")
                            ->orWhereLike('ci', "{$search}%"),
                    ))
                    ->weight('medium'),
                TextColumn::make('extracto')
                    ->label('Extracto')
                    ->limit(40),
                TextColumn::make('fase')
                    ->label('Fase')
                    ->badge(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('proximaAplicacion.fecha_prevista')
                    ->label('Próxima dosis')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('atraso')
                    ->label('Atraso')
                    ->state(fn (Inmunoterapia $record): ?string => ($dias = $record->proximaAplicacion?->diasDeAtraso()) > 0 ? "{$dias} días" : null)
                    ->badge()
                    ->color('warning')
                    ->placeholder('Al día'),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(EstadoInmunoterapia::class)
                    ->default(EstadoInmunoterapia::Activa->value),
                SelectFilter::make('fase')
                    ->label('Fase')
                    ->options(FaseInmunoterapia::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
