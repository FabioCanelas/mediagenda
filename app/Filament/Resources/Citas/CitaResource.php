<?php

namespace App\Filament\Resources\Citas;

use App\Filament\Resources\Citas\Pages\AgendaSemanal;
use App\Filament\Resources\Citas\Pages\CreateCita;
use App\Filament\Resources\Citas\Pages\EditCita;
use App\Filament\Resources\Citas\Pages\ListCitas;
use App\Filament\Resources\Citas\Schemas\CitaForm;
use App\Filament\Resources\Citas\Tables\CitasTable;
use App\Models\Cita;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * HU-14: agenda de citas. Doctora y recepción la usan.
 */
class CitaResource extends Resource
{
    protected static ?string $model = Cita::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Consultorio';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Agenda';

    protected static ?string $modelLabel = 'cita';

    protected static ?string $pluralModelLabel = 'citas';

    protected static ?string $slug = 'agenda';

    public static function form(Schema $schema): Schema
    {
        return CitaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CitasTable::configure($table);
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof Cita
            ? "Cita de {$record->paciente->nombre_completo} · {$record->inicio->format('d/m/Y H:i')}"
            : parent::getRecordTitle($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCitas::route('/'),
            'semana' => AgendaSemanal::route('/semana'),
            'create' => CreateCita::route('/create'),
            'edit' => EditCita::route('/{record}/edit'),
        ];
    }
}
