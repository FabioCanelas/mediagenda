<?php

namespace App\Filament\Resources\Citas\Tables;

use App\Enums\EstadoCita;
use App\Enums\TipoCita;
use App\Filament\Resources\Pacientes\AccionesPreconsulta;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Preconsulta;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class CitasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('paciente'))
            ->defaultSort('inicio')
            ->columns([
                TextColumn::make('inicio')
                    ->label('Día y hora')
                    ->dateTime('D d/m · H:i')
                    ->sortable(),
                TextColumn::make('paciente')
                    ->label('Paciente')
                    ->state(fn (Cita $record): string => $record->paciente->nombre_completo)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'paciente',
                        fn (Builder $paciente) => $paciente
                            ->whereLike('nombres', "%{$search}%")
                            ->orWhereLike('apellido_paterno', "%{$search}%")
                            ->orWhereLike('apellido_materno', "%{$search}%")
                            ->orWhereLike('ci', "{$search}%"),
                    ))
                    ->weight('medium'),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('duracion')
                    ->label('Duración')
                    ->state(fn (Cita $record): string => $record->inicio->diffInMinutes($record->fin).' min'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
            ])
            ->filters([
                Filter::make('dia')
                    ->schema([
                        DatePicker::make('desde')->label('Desde')->native(false)->displayFormat('d/m/Y')->default(now()),
                        DatePicker::make('hasta')->label('Hasta')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, string $desde) => $q->where('inicio', '>=', Carbon::parse($desde)->startOfDay()))
                        ->when($data['hasta'] ?? null, fn (Builder $q, string $hasta) => $q->where('inicio', '<=', Carbon::parse($hasta)->endOfDay())))
                    ->indicateUsing(fn (array $data): ?string => ($data['desde'] ?? null) ? 'Desde '.Carbon::parse($data['desde'])->format('d/m/Y') : null),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(EstadoCita::class),
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(TipoCita::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    AccionesPreconsulta::prepararTablet(fn (Cita $record): Paciente => $record->paciente, fn (Cita $record): Cita => $record)
                        ->visible(fn (Cita $record): bool => $record->estaVigente() && Gate::allows('create', Preconsulta::class)),
                    self::cambiarEstado('confirmar', 'Confirmar', EstadoCita::Confirmada, Heroicon::OutlinedCheck, [EstadoCita::Programada]),
                    self::cambiarEstado('atendida', 'Marcar atendida', EstadoCita::Atendida, Heroicon::OutlinedCheckBadge, [EstadoCita::Programada, EstadoCita::Confirmada]),
                    self::cambiarEstado('noAsistio', 'No asistió', EstadoCita::NoAsistio, Heroicon::OutlinedUserMinus, [EstadoCita::Programada, EstadoCita::Confirmada]),
                    Action::make('cancelar')
                        ->label('Cancelar')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->visible(fn (Cita $record): bool => in_array($record->estado, [EstadoCita::Programada, EstadoCita::Confirmada], true))
                        ->authorize('update')
                        ->modalHeading('Cancelar cita')
                        ->modalDescription('La cita se conserva en el historial y su horario queda libre.')
                        ->schema([
                            TextInput::make('motivo_cancelacion')
                                ->label('Motivo')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(fn (Cita $record, array $data) => $record->update([
                            'estado' => EstadoCita::Cancelada,
                            'motivo_cancelacion' => $data['motivo_cancelacion'],
                        ])),
                ]),
                EditAction::make()
                    ->label('Mover o editar'),
            ]);
    }

    /**
     * @param  array<int, EstadoCita>  $desde  Estados desde los que se permite el cambio.
     */
    private static function cambiarEstado(string $nombre, string $etiqueta, EstadoCita $estado, Heroicon $icono, array $desde): Action
    {
        return Action::make($nombre)
            ->label($etiqueta)
            ->icon($icono)
            ->color($estado->getColor())
            ->visible(fn (Cita $record): bool => in_array($record->estado, $desde, true))
            ->authorize('update')
            ->action(fn (Cita $record) => $record->update(['estado' => $estado]));
    }
}
