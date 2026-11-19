<?php

namespace App\Filament\Resources\Pacientes\RelationManagers;

use App\Filament\Resources\PruebasCutaneas\PruebaCutaneaResource;
use App\Models\PruebaCutanea;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PruebasCutaneasRelationManager extends RelationManager
{
    protected static string $relationship = 'pruebasCutaneas';

    protected static ?string $title = 'Prick test';

    protected static ?string $modelLabel = 'prick test';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('fecha')
            ->defaultSort('fecha', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount(['resultados as positivos_count' => fn (Builder $resultados) => $resultados->where('positivo', true)])
                ->with('realizadoPor'))
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
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
            ->recordUrl(fn (PruebaCutanea $record): string => PruebaCutaneaResource::getUrl('view', ['record' => $record]))
            ->headerActions([
                Action::make('nuevoPrickTest')
                    ->label('Nuevo prick test')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => PruebaCutaneaResource::getUrl('create', ['paciente' => $this->getOwnerRecord()->getKey()])),
            ]);
    }
}
