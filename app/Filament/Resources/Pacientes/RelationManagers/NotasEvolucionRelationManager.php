<?php

namespace App\Filament\Resources\Pacientes\RelationManagers;

use App\Filament\Resources\NotasEvolucion\NotaEvolucionResource;
use App\Models\NotaEvolucion;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotasEvolucionRelationManager extends RelationManager
{
    protected static string $relationship = 'notasEvolucion';

    protected static ?string $title = 'Notas de evolución';

    protected static ?string $modelLabel = 'nota de evolución';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('fecha')
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('fecha')->label('Fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('evolucion')->label('Evolución')->limit(70),
                TextColumn::make('estado')->label('Estado')->badge(),
            ])
            ->recordUrl(fn (NotaEvolucion $record): string => NotaEvolucionResource::getUrl('view', ['record' => $record]))
            ->headerActions([
                Action::make('nuevaNota')
                    ->label('Nueva nota')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => NotaEvolucionResource::getUrl('create', ['paciente' => $this->getOwnerRecord()->getKey()])),
            ]);
    }
}
