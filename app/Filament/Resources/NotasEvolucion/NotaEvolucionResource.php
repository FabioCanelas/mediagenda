<?php

namespace App\Filament\Resources\NotasEvolucion;

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
use Illuminate\Support\Str;
use UnitEnum;

class NotaEvolucionResource extends Resource
{
    protected static ?string $model = NotaEvolucion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Consultorio';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Notas de evolución';

    protected static ?string $modelLabel = 'nota de evolución';

    protected static ?string $pluralModelLabel = 'notas de evolución';

    protected static ?string $slug = 'notas-evolucion';

    /**
     * En español solo la primera palabra del título va con mayúscula.
     */
    public static function getTitleCaseModelLabel(): string
    {
        return Str::ucfirst(static::getModelLabel());
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return Str::ucfirst(static::getPluralModelLabel());
    }

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
