<?php

namespace App\Filament\Resources\NotasEvolucion;

use App\Filament\Resources\Concerns\TitulosEnEspanol;
use App\Filament\Resources\NotasEvolucion\Pages\CreateNotaEvolucion;
use App\Filament\Resources\NotasEvolucion\Pages\EditNotaEvolucion;
use App\Filament\Resources\NotasEvolucion\Pages\ListNotasEvolucion;
use App\Filament\Resources\NotasEvolucion\Pages\ViewNotaEvolucion;
use App\Filament\Resources\NotasEvolucion\Schemas\NotaEvolucionForm;
use App\Filament\Resources\NotasEvolucion\Schemas\NotaEvolucionInfolist;
use App\Filament\Resources\NotasEvolucion\Tables\NotasEvolucionTable;
use App\Filament\Widgets\AvisoAlergias;
use App\Models\NotaEvolucion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class NotaEvolucionResource extends Resource
{
    use TitulosEnEspanol;

    protected static ?string $model = NotaEvolucion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Consultorio';

    /**
     * Fuera del menú: se abre desde la ficha del paciente (pestañas y botones)
     * para que la doctora tenga menos lugares donde buscar.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Notas de evolución';

    protected static ?string $modelLabel = 'nota de evolución';

    protected static ?string $pluralModelLabel = 'notas de evolución';

    protected static ?string $slug = 'notas-evolucion';

    public static function form(Schema $schema): Schema
    {
        return NotaEvolucionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NotaEvolucionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotasEvolucionTable::configure($table);
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof NotaEvolucion
            ? "Nota del {$record->fecha->format('d/m/Y')} · {$record->paciente->nombre_completo}"
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
            'index' => ListNotasEvolucion::route('/'),
            'create' => CreateNotaEvolucion::route('/create'),
            'view' => ViewNotaEvolucion::route('/{record}'),
            'edit' => EditNotaEvolucion::route('/{record}/edit'),
        ];
    }
}
