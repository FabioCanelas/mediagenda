<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Citas\CitaResource;
use App\Models\Cita;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class CitasDeHoy extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Citas de hoy')
            ->query(fn (): Builder => Cita::query()
                ->with('paciente')
                ->delDia(now())
                ->where('estado', '!=', 'cancelada')
                ->orderBy('inicio'))
            ->columns([
                TextColumn::make('inicio')->label('Hora')->dateTime('H:i'),
                TextColumn::make('paciente')
                    ->label('Paciente')
                    ->state(fn (Cita $record): string => $record->paciente->nombre_completo)
                    ->weight('medium'),
                TextColumn::make('tipo')->label('Tipo')->badge(),
                TextColumn::make('estado')->label('Estado')->badge(),
            ])
            ->recordUrl(fn (Cita $record): string => CitaResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading('No hay citas para hoy')
            ->paginated(false);
    }
}
