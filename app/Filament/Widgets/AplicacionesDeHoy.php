<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inmunoterapias\InmunoterapiaResource;
use App\Models\ProximaAplicacion;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Inmunoterapias a las que les toca dosis hoy (vista v_proxima_aplicacion).
 */
class AplicacionesDeHoy extends TableWidget
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
            ->heading('Aplicaciones que tocan hoy')
            ->query(fn (): Builder => ProximaAplicacion::query()
                ->with(['paciente', 'inmunoterapia'])
                ->whereDate('fecha_prevista', now()->toDateString()))
            ->columns([
                TextColumn::make('paciente')
                    ->label('Paciente')
                    ->state(fn (ProximaAplicacion $record): string => $record->paciente->nombre_completo)
                    ->weight('medium'),
                TextColumn::make('fase')->label('Fase')->badge(),
                TextColumn::make('dosis')
                    ->label('Dosis según el esquema')
                    ->state(fn (ProximaAplicacion $record): ?string => $record->inmunoterapia->dosisEsperada()['descripcion'] ?? null)
                    ->placeholder('Esquema completo: pasar a mantenimiento'),
            ])
            ->recordUrl(fn (ProximaAplicacion $record): string => InmunoterapiaResource::getUrl('view', ['record' => $record->inmunoterapia_id]))
            ->emptyStateHeading('No hay aplicaciones previstas para hoy')
            ->paginated(false);
    }
}
