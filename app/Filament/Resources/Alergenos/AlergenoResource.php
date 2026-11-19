<?php

namespace App\Filament\Resources\Alergenos;

use App\Enums\GrupoAlergeno;
use App\Filament\Resources\Alergenos\Pages\ManageAlergenos;
use App\Models\Alergeno;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class AlergenoResource extends Resource
{
    protected static ?string $model = Alergeno::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Panel de alérgenos';

    protected static ?string $modelLabel = 'alérgeno';

    protected static ?string $pluralModelLabel = 'alérgenos';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),
                TextInput::make('nombre_cientifico')
                    ->label('Nombre científico')
                    ->maxLength(120),
                Select::make('grupo')
                    ->label('Grupo')
                    ->options(GrupoAlergeno::class)
                    ->required(),
                Toggle::make('activo')
                    ->label('Se usa en el panel')
                    ->default(true)
                    ->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->reorderable('orden')
            ->defaultSort('orden')
            ->columns([
                TextColumn::make('nombre')
                    ->label('Alérgeno')
                    ->searchable(),
                TextColumn::make('nombre_cientifico')
                    ->label('Nombre científico')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('grupo')
                    ->label('Grupo')
                    ->badge(),
                IconColumn::make('activo')
                    ->label('En el panel')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('grupo')
                    ->label('Grupo')
                    ->options(GrupoAlergeno::class),
                TernaryFilter::make('activo')
                    ->label('En el panel'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAlergenos::route('/'),
        ];
    }
}
