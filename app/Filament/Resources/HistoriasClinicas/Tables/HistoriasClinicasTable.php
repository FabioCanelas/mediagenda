<?php

namespace App\Filament\Resources\HistoriasClinicas\Tables;

use App\Models\HistoriaClinica;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HistoriasClinicasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('paciente')->withCount('diagnosticos'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('paciente')
                    ->label('Paciente')
                    ->state(fn (HistoriaClinica $record): string => $record->paciente->nombre_completo)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'paciente',
                        fn (Builder $paciente) => $paciente
                            ->whereLike('nombres', "%{$search}%")
                            ->orWhereLike('apellido_paterno', "%{$search}%")
                            ->orWhereLike('apellido_materno', "%{$search}%")
                            ->orWhereLike('ci', "{$search}%"),
                    ))
                    ->weight('medium'),
                TextColumn::make('paciente.ci')
                    ->label('CI'),
                TextColumn::make('diagnosticos_count')
                    ->label('Diagnósticos')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('updated_at')
                    ->label('Última modificación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
