<?php

namespace App\Filament\Resources\ReglasAtraso;

use App\Enums\FaseInmunoterapia;
use App\Filament\Resources\Concerns\TitulosEnEspanol;
use App\Filament\Resources\ReglasAtraso\Pages\ManageReglasAtraso;
use App\Models\ReglaAtraso;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * HU-13: reglas de la doctora para los atrasos de la inmunoterapia.
 */
class ReglaAtrasoResource extends Resource
{
    use TitulosEnEspanol;

    protected static ?string $model = ReglaAtraso::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Reglas de atraso';

    protected static ?string $modelLabel = 'regla de atraso';

    protected static ?string $pluralModelLabel = 'reglas de atraso';

    protected static ?string $slug = 'reglas-atraso';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Select::make('fase')
                    ->label('Fase')
                    ->options(FaseInmunoterapia::class)
                    ->required(),
                TextInput::make('dias_desde')
                    ->label('Desde')
                    ->suffix('días de atraso')
                    ->integer()
                    ->minValue(1)
                    ->required(),
                TextInput::make('dias_hasta')
                    ->label('Hasta')
                    ->suffix('días')
                    ->helperText('Vacío = en adelante.')
                    ->integer()
                    ->gte('dias_desde'),
                Textarea::make('accion')
                    ->label('Qué hacer')
                    ->placeholder('Repetir la dosis anterior, reducir la dosis un 25 %…')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Toggle::make('activo')
                    ->label('Regla vigente')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('El sistema muestra la regla que corresponde al atraso; nunca cambia la dosis por su cuenta.')
            ->defaultSort('dias_desde')
            ->defaultGroup('fase')
            ->columns([
                TextColumn::make('tramo')
                    ->label('Atraso')
                    ->state(fn (ReglaAtraso $record): string => $record->tramo()),
                TextColumn::make('accion')
                    ->label('Qué hacer')
                    ->wrap(),
                IconColumn::make('activo')
                    ->label('Vigente')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageReglasAtraso::route('/'),
        ];
    }
}
