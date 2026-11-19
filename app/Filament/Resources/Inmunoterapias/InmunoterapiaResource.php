<?php

namespace App\Filament\Resources\Inmunoterapias;

use App\Filament\Resources\Inmunoterapias\Pages\CreateInmunoterapia;
use App\Filament\Resources\Inmunoterapias\Pages\EditInmunoterapia;
use App\Filament\Resources\Inmunoterapias\Pages\ListInmunoterapias;
use App\Filament\Resources\Inmunoterapias\Pages\ViewInmunoterapia;
use App\Filament\Resources\Inmunoterapias\RelationManagers\AplicacionesRelationManager;
use App\Filament\Resources\Inmunoterapias\RelationManagers\FrascosRelationManager;
use App\Filament\Resources\Inmunoterapias\Schemas\InmunoterapiaForm;
use App\Filament\Resources\Inmunoterapias\Schemas\InmunoterapiaInfolist;
use App\Filament\Resources\Inmunoterapias\Tables\InmunoterapiasTable;
use App\Filament\Widgets\AvisoAlergias;
use App\Models\Inmunoterapia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class InmunoterapiaResource extends Resource
{
    protected static ?string $model = Inmunoterapia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Consultorio';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Inmunoterapia';

    protected static ?string $modelLabel = 'inmunoterapia';

    protected static ?string $pluralModelLabel = 'inmunoterapias';

    public static function form(Schema $schema): Schema
    {
        return InmunoterapiaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InmunoterapiaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InmunoterapiasTable::configure($table);
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof Inmunoterapia
            ? "Inmunoterapia de {$record->paciente->nombre_completo}"
            : parent::getRecordTitle($record);
    }

    public static function getRelations(): array
    {
        return [
            AplicacionesRelationManager::class,
            FrascosRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            AvisoAlergias::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInmunoterapias::route('/'),
            'create' => CreateInmunoterapia::route('/create'),
            'view' => ViewInmunoterapia::route('/{record}'),
            'edit' => EditInmunoterapia::route('/{record}/edit'),
        ];
    }
}
