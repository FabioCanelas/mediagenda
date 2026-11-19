<?php

namespace App\Filament\Resources\Pacientes\RelationManagers;

use App\Filament\Resources\Citas\CitaResource;
use App\Models\Cita;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Citas del paciente. La recepción también las ve.
 */
class CitasRelationManager extends RelationManager
{
    protected static string $relationship = 'citas';

    protected static ?string $title = 'Citas';

    protected static ?string $modelLabel = 'cita';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('inicio')
            ->defaultSort('inicio', 'desc')
            ->columns([
                TextColumn::make('inicio')->label('Día y hora')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('tipo')->label('Tipo')->badge(),
                TextColumn::make('estado')->label('Estado')->badge(),
            ])
            ->recordUrl(fn (Cita $record): string => CitaResource::getUrl('edit', ['record' => $record]))
            ->headerActions([
                Action::make('agendar')
                    ->label('Agendar cita')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => CitaResource::getUrl('create', ['paciente' => $this->getOwnerRecord()->getKey()])),
            ]);
    }
}
