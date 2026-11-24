<?php

namespace App\Filament\Resources\HistoriasClinicas;

use App\Filament\Resources\Concerns\TitulosEnEspanol;
use App\Filament\Resources\HistoriasClinicas\Pages\CreateHistoriaClinica;
use App\Filament\Resources\HistoriasClinicas\Pages\EditHistoriaClinica;
use App\Filament\Resources\HistoriasClinicas\Pages\ListHistoriasClinicas;
use App\Filament\Resources\HistoriasClinicas\Pages\ViewHistoriaClinica;
use App\Filament\Resources\HistoriasClinicas\Schemas\HistoriaClinicaForm;
use App\Filament\Resources\HistoriasClinicas\Schemas\HistoriaClinicaInfolist;
use App\Filament\Resources\HistoriasClinicas\Tables\HistoriasClinicasTable;
use App\Filament\Widgets\AvisoAlergias;
use App\Models\HistoriaClinica;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class HistoriaClinicaResource extends Resource
{
    use TitulosEnEspanol;

    protected static ?string $model = HistoriaClinica::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Consultorio';

    /**
     * Fuera del menú: se abre desde la ficha del paciente (pestañas y botones)
     * para que la doctora tenga menos lugares donde buscar.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'historia clínica';

    protected static ?string $pluralModelLabel = 'historias clínicas';

    protected static ?string $slug = 'historias-clinicas';

    public static function form(Schema $schema): Schema
    {
        return HistoriaClinicaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HistoriaClinicaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HistoriasClinicasTable::configure($table);
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof HistoriaClinica
            ? 'Historia clínica de '.$record->paciente->nombre_completo
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
            'index' => ListHistoriasClinicas::route('/'),
            'create' => CreateHistoriaClinica::route('/create'),
            'view' => ViewHistoriaClinica::route('/{record}'),
            'edit' => EditHistoriaClinica::route('/{record}/edit'),
        ];
    }
}
