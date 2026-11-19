<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoPreconsulta;
use App\Filament\Resources\Pacientes\PacienteResource;
use App\Models\Preconsulta;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * HU-05 y HU-15: pre-consultas que los pacientes ya enviaron desde la tablet.
 */
class PreconsultasPorRevisar extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->esDoctora() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pre-consultas por revisar')
            ->query(fn (): Builder => Preconsulta::query()
                ->with('paciente')
                ->where('estado', EstadoPreconsulta::Enviada)
                ->orderBy('enviada_en'))
            ->columns([
                TextColumn::make('paciente')
                    ->label('Paciente')
                    ->state(fn (Preconsulta $record): string => $record->paciente->nombre_completo)
                    ->weight('medium'),
                TextColumn::make('motivo_consulta')
                    ->label('Motivo')
                    ->limit(60),
                TextColumn::make('enviada_en')
                    ->label('Enviada')
                    ->since(),
            ])
            ->recordUrl(fn (Preconsulta $record): string => PacienteResource::getUrl('view', ['record' => $record->paciente_id]))
            ->emptyStateHeading('No hay pre-consultas pendientes')
            ->paginated(false);
    }
}
