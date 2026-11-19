<?php

namespace App\Filament\Resources\PruebasCutaneas\Tables;

use App\Models\PruebaCutanea;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PruebasCutaneasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['paciente', 'realizadoPor'])
                ->withCount(['resultados as positivos_count' => fn (Builder $resultados) => $resultados->where('positivo', true)]))
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('paciente')
                    ->label('Paciente')
                    ->state(fn (PruebaCutanea $record): string => $record->paciente->nombre_completo)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'paciente',
                        fn (Builder $paciente) => $paciente
                            ->where('nombres', 'like', "%{$search}%")
                            ->orWhere('apellido_paterno', 'like', "%{$search}%")
                            ->orWhere('apellido_materno', 'like', "%{$search}%")
                            ->orWhere('ci', 'like', "{$search}%"),
                    ))
                    ->weight('medium'),
                IconColumn::make('interpretable')
                    ->label('Interpretable')
                    ->boolean(),
                TextColumn::make('positivos_count')
                    ->label('Positivos')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('realizadoPor.name')
                    ->label('Realizó')
                    ->placeholder('—'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
