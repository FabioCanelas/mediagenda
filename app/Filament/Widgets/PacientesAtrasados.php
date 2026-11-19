<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inmunoterapias\InmunoterapiaResource;
use App\Models\ProximaAplicacion;
use App\Models\ReglaAtraso;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * HU-13 y HU-15: pacientes con la vacuna atrasada y la regla que definió la doctora.
 */
class PacientesAtrasados extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->esDoctora() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pacientes atrasados')
            ->query(fn (): Builder => ProximaAplicacion::query()
                ->with('paciente')
                ->whereDate('fecha_prevista', '<', now()->toDateString())
                ->orderBy('fecha_prevista'))
            ->columns([
                TextColumn::make('paciente')
                    ->label('Paciente')
                    ->state(fn (ProximaAplicacion $record): string => $record->paciente->nombre_completo)
                    ->weight('medium'),
                TextColumn::make('fecha_prevista')->label('Le tocaba')->date('d/m/Y'),
                TextColumn::make('dias')
                    ->label('Atraso')
                    ->state(fn (ProximaAplicacion $record): string => $record->diasDeAtraso().' días')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('regla')
                    ->label('Regla de la doctora')
                    ->state(fn (ProximaAplicacion $record): ?string => ReglaAtraso::paraAtraso($record->fase, $record->diasDeAtraso())?->accion)
                    ->placeholder('Sin regla para este atraso')
                    ->wrap(),
            ])
            ->recordUrl(fn (ProximaAplicacion $record): string => InmunoterapiaResource::getUrl('view', ['record' => $record->inmunoterapia_id]))
            ->emptyStateHeading('Ningún paciente atrasado')
            ->paginated(false);
    }
}
