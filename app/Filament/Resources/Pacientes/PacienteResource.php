<?php

namespace App\Filament\Resources\Pacientes;

use App\Filament\Resources\Pacientes\Pages\CreatePaciente;
use App\Filament\Resources\Pacientes\Pages\EditPaciente;
use App\Filament\Resources\Pacientes\Pages\ListPacientes;
use App\Filament\Resources\Pacientes\Pages\ViewPaciente;
use App\Filament\Resources\Pacientes\RelationManagers\AlergiasRelationManager;
use App\Filament\Resources\Pacientes\RelationManagers\CitasRelationManager;
use App\Filament\Resources\Pacientes\RelationManagers\InmunoterapiasRelationManager;
use App\Filament\Resources\Pacientes\RelationManagers\NotasEvolucionRelationManager;
use App\Filament\Resources\Pacientes\RelationManagers\PruebasCutaneasRelationManager;
use App\Filament\Resources\Pacientes\Schemas\PacienteForm;
use App\Filament\Resources\Pacientes\Schemas\PacienteInfolist;
use App\Filament\Resources\Pacientes\Tables\PacientesTable;
use App\Filament\Widgets\AvisoAlergias;
use App\Models\Paciente;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PacienteResource extends Resource
{
    protected static ?string $model = Paciente::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Consultorio';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'paciente';

    protected static ?string $pluralModelLabel = 'pacientes';

    protected static ?string $recordTitleAttribute = 'nombres';

    public static function form(Schema $schema): Schema
    {
        return PacienteForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PacienteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PacientesTable::configure($table);
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof Paciente ? $record->nombre_completo : parent::getRecordTitle($record);
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['ci', 'nombres', 'apellido_paterno', 'apellido_materno'];
    }

    public static function getRelations(): array
    {
        return [
            AlergiasRelationManager::class,
            CitasRelationManager::class,
            NotasEvolucionRelationManager::class,
            InmunoterapiasRelationManager::class,
            PruebasCutaneasRelationManager::class,
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
            'index' => ListPacientes::route('/'),
            'create' => CreatePaciente::route('/create'),
            'view' => ViewPaciente::route('/{record}'),
            'edit' => EditPaciente::route('/{record}/edit'),
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
