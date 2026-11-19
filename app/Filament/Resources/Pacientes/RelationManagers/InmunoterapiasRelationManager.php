<?php

namespace App\Filament\Resources\Pacientes\RelationManagers;

use App\Filament\Resources\Inmunoterapias\InmunoterapiaResource;
use App\Models\Inmunoterapia;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InmunoterapiasRelationManager extends RelationManager
{
    protected static string $relationship = 'inmunoterapias';

    protected static ?string $title = 'Inmunoterapia';

    protected static ?string $modelLabel = 'inmunoterapia';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('extracto')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('proximaAplicacion'))
            ->defaultSort('fecha_inicio', 'desc')
            ->columns([
                TextColumn::make('extracto')->label('Extracto')->limit(40),
                TextColumn::make('fase')->label('Fase')->badge(),
                TextColumn::make('estado')->label('Estado')->badge(),
                TextColumn::make('proximaAplicacion.fecha_prevista')->label('Próxima dosis')->date('d/m/Y')->placeholder('—'),
            ])
            ->recordUrl(fn (Inmunoterapia $record): string => InmunoterapiaResource::getUrl('view', ['record' => $record]))
            ->headerActions([
                Action::make('nuevaInmunoterapia')
                    ->label('Indicar inmunoterapia')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => InmunoterapiaResource::getUrl('create', ['paciente' => $this->getOwnerRecord()->getKey()])),
            ]);
    }
}
