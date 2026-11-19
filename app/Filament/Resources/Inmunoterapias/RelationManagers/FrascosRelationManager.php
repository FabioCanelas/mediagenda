<?php

namespace App\Filament\Resources\Inmunoterapias\RelationManagers;

use App\Enums\Dilucion;
use App\Models\Frasco;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * HU-10: frascos del tratamiento. El color lo asigna el sistema según la dilución.
 */
class FrascosRelationManager extends RelationManager
{
    protected static string $relationship = 'frascos';

    protected static ?string $title = 'Frascos';

    protected static ?string $modelLabel = 'frasco';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('dilucion')
                    ->label('Dilución')
                    ->helperText('El color del frasco se asigna solo.')
                    ->options(Dilucion::class)
                    ->required(),
                TextInput::make('lote')
                    ->label('Lote')
                    ->required()
                    ->maxLength(40),
                DatePicker::make('fecha_preparacion')
                    ->label('Preparado el')
                    ->default(now())
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->maxDate(now()),
                DatePicker::make('fecha_vencimiento')
                    ->label('Vence el')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('fecha_preparacion')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('lote')
            ->defaultSort('fecha_vencimiento')
            ->columns([
                TextColumn::make('dilucion')
                    ->label('Frasco')
                    ->badge(),
                TextColumn::make('lote')
                    ->label('Lote'),
                TextColumn::make('fecha_preparacion')
                    ->label('Preparado')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('fecha_vencimiento')
                    ->label('Vence')
                    ->date('d/m/Y'),
                TextColumn::make('situacion')
                    ->label('Situación')
                    ->badge()
                    ->state(fn (Frasco $record): string => match (true) {
                        ! $record->activo => 'De baja',
                        $record->estaVencido() => 'Vencido',
                        default => 'Vigente',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Vigente' => 'success',
                        'Vencido' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar frasco'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('darDeBaja')
                    ->label('Dar de baja')
                    ->icon(Heroicon::OutlinedArchiveBoxXMark)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('El frasco ya no se podrá usar en nuevas aplicaciones. Sus aplicaciones anteriores se conservan.')
                    ->visible(fn (Frasco $record): bool => $record->activo)
                    ->authorize('update')
                    ->action(fn (Frasco $record) => $record->update(['activo' => false])),
            ]);
    }
}
