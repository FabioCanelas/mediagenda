<?php

namespace App\Filament\Resources\PruebasCutaneas;

use App\Filament\Resources\Concerns\TitulosEnEspanol;
use App\Filament\Resources\PruebasCutaneas\Pages\CreatePruebaCutanea;
use App\Filament\Resources\PruebasCutaneas\Pages\EditPruebaCutanea;
use App\Filament\Resources\PruebasCutaneas\Pages\ListPruebasCutaneas;
use App\Filament\Resources\PruebasCutaneas\Pages\ViewPruebaCutanea;
use App\Filament\Resources\PruebasCutaneas\Schemas\PruebaCutaneaForm;
use App\Filament\Resources\PruebasCutaneas\Schemas\PruebaCutaneaInfolist;
use App\Filament\Resources\PruebasCutaneas\Tables\PruebasCutaneasTable;
use App\Filament\Widgets\AvisoAlergias;
use App\Models\PruebaCutanea;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PruebaCutaneaResource extends Resource
{
    use TitulosEnEspanol;

    protected static ?string $model = PruebaCutanea::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|UnitEnum|null $navigationGroup = 'Consultorio';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Prick test';

    protected static ?string $modelLabel = 'prick test';

    protected static ?string $pluralModelLabel = 'prick tests';

    protected static ?string $slug = 'prick-test';

    public static function form(Schema $schema): Schema
    {
        return PruebaCutaneaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PruebaCutaneaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PruebasCutaneasTable::configure($table);
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof PruebaCutanea
            ? "Prick test de {$record->paciente->nombre_completo} · {$record->fecha->format('d/m/Y')}"
            : parent::getRecordTitle($record);
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
            'index' => ListPruebasCutaneas::route('/'),
            'create' => CreatePruebaCutanea::route('/create'),
            'view' => ViewPruebaCutanea::route('/{record}'),
            'edit' => EditPruebaCutanea::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
