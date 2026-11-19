<?php

namespace App\Filament\Resources\Pacientes\RelationManagers;

use App\Enums\CategoriaAlergia;
use App\Enums\CriticidadAlergia;
use App\Enums\EstadoAlergia;
use App\Enums\GravedadReaccion;
use App\Enums\TipoAlergia;
use App\Models\Alergia;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * HU-04: alergias del paciente. Una alergia errónea se marca como descartada:
 * sale del aviso rojo pero se conserva en el historial.
 */
class AlergiasRelationManager extends RelationManager
{
    protected static string $relationship = 'alergias';

    protected static ?string $title = 'Alergias';

    protected static ?string $modelLabel = 'alergia';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('sustancia')
                    ->label('Sustancia')
                    ->placeholder('Penicilina, maní, ácaros del polvo…')
                    ->required()
                    ->maxLength(120),
                Select::make('categoria')
                    ->label('Categoría')
                    ->options(CategoriaAlergia::class)
                    ->required(),
                Select::make('tipo')
                    ->label('Tipo')
                    ->options(TipoAlergia::class)
                    ->default(TipoAlergia::Alergia)
                    ->required(),
                Select::make('estado')
                    ->label('Estado')
                    ->options(EstadoAlergia::class)
                    ->default(EstadoAlergia::Sospechada)
                    ->required(),
                Select::make('criticidad')
                    ->label('Criticidad')
                    ->helperText('Qué tan grave sería volver a exponerse.')
                    ->options(CriticidadAlergia::class)
                    ->default(CriticidadAlergia::NoEvaluable)
                    ->required(),
                Select::make('gravedad')
                    ->label('Gravedad de la reacción')
                    ->options(GravedadReaccion::class),
                Textarea::make('reaccion')
                    ->label('Reacción observada')
                    ->placeholder('Urticaria, edema de labios, dificultad para respirar…')
                    ->rows(2)
                    ->columnSpanFull(),
                DatePicker::make('fecha_inicio')
                    ->label('Desde cuándo')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->maxDate(now()),
                Textarea::make('notas')
                    ->label('Notas')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sustancia')
            ->defaultSort('estado')
            ->columns([
                TextColumn::make('sustancia')
                    ->label('Sustancia')
                    ->weight('medium')
                    ->searchable(),
                TextColumn::make('categoria')
                    ->label('Categoría')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('criticidad')
                    ->label('Criticidad')
                    ->badge(),
                TextColumn::make('reaccion')
                    ->label('Reacción')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(EstadoAlergia::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar alergia'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('descartar')
                    ->label('Descartar')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('La alergia sale del aviso, pero queda guardada en el historial del paciente.')
                    ->visible(fn (Alergia $record): bool => $record->estaVigente())
                    ->authorize('update')
                    ->action(fn (Alergia $record) => $record->update(['estado' => EstadoAlergia::Descartada])),
            ]);
    }
}
