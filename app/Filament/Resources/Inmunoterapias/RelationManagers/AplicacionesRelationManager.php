<?php

namespace App\Filament\Resources\Inmunoterapias\RelationManagers;

use App\Enums\Brazo;
use App\Enums\FaseInmunoterapia;
use App\Enums\GradoWao;
use App\Enums\ProcedimientoConsentimiento;
use App\Enums\ReaccionLocal;
use App\Enums\TipoCita;
use App\Filament\Resources\Citas\CitaResource;
use App\Models\Aplicacion;
use App\Models\EsquemaDosis;
use App\Models\Frasco;
use App\Models\Inmunoterapia;
use App\Models\ReaccionSistemica;
use App\Support\BaseDeDatos\ErrorDeBase;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * HU-11 y HU-12: aplicaciones de la vacuna, observación de 30 minutos
 * y reacciones sistémicas.
 *
 * @method Inmunoterapia getOwnerRecord()
 */
class AplicacionesRelationManager extends RelationManager
{
    protected static string $relationship = 'aplicaciones';

    protected static ?string $title = 'Aplicaciones';

    protected static ?string $modelLabel = 'aplicación';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['frasco', 'esquemaDosis', 'reaccionSistemica']))
            ->defaultSort('aplicada_en', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('aplicada_en')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('frasco.dilucion')
                    ->label('Frasco')
                    ->badge(),
                TextColumn::make('volumen_ml')
                    ->label('Volumen')
                    ->suffix(' mL'),
                TextColumn::make('esquemaDosis.paso')
                    ->label('Paso')
                    ->placeholder('Mant.'),
                TextColumn::make('dosis_difiere_esquema')
                    ->label('Dosis')
                    ->badge()
                    ->state(fn (Aplicacion $record): string => $record->dosis_difiere_esquema ? 'Distinta al esquema' : 'Según esquema')
                    ->color(fn (string $state): string => $state === 'Según esquema' ? 'gray' : 'warning'),
                TextColumn::make('brazo')
                    ->label('Brazo'),
                TextColumn::make('reaccion_local')
                    ->label('Reacción local')
                    ->badge(),
                TextColumn::make('observacion')
                    ->label('Observación')
                    ->badge()
                    ->state(fn (Aplicacion $record): string => match (true) {
                        $record->observacionCompletada() => 'Completada',
                        $record->puedeTerminarObservacion() => 'Lista para terminar',
                        default => 'Hasta las '.$record->observacion_hasta->format('H:i'),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Completada' => 'success',
                        'Lista para terminar' => 'info',
                        default => 'warning',
                    }),
                TextColumn::make('reaccionSistemica.grado')
                    ->label('Reacción sistémica')
                    ->badge()
                    ->formatStateUsing(fn (GradoWao $state): string => "Grado {$state->value}")
                    ->placeholder('—'),
            ])
            ->headerActions([
                $this->accionRegistrarAplicacion(),
            ])
            ->recordActions([
                $this->accionTerminarObservacion(),
                $this->accionReaccionSistemica(),
            ]);
    }

    private function accionRegistrarAplicacion(): Action
    {
        return Action::make('registrarAplicacion')
            ->label('Registrar aplicación')
            ->icon(Heroicon::OutlinedPlus)
            ->visible(fn (): bool => $this->getOwnerRecord()->admiteAplicaciones() && Gate::allows('create', Aplicacion::class))
            ->modalHeading('Registrar aplicación')
            ->modalSubmitActionLabel('Registrar')
            ->fillForm(fn (): array => $this->valoresIniciales())
            ->schema(fn (): array => $this->formularioAplicacion())
            ->action(function (array $data, Action $action): void {
                try {
                    $aplicacion = $this->getOwnerRecord()->aplicaciones()->create(Arr::except($data, ['confirmar_dosis_distinta']));
                } catch (QueryException $error) {
                    Notification::make()
                        ->title('La base de datos rechazó la aplicación')
                        ->body(ErrorDeBase::mensaje($error))
                        ->danger()
                        ->send();

                    $action->halt();

                    return;
                }

                Notification::make()
                    ->title('Aplicación registrada')
                    ->body('Mantén al paciente en observación hasta las '.$aplicacion->observacion_hasta->format('H:i').'.')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function valoresIniciales(): array
    {
        $inmunoterapia = $this->getOwnerRecord();
        $esperada = $inmunoterapia->dosisEsperada();

        $frasco = $inmunoterapia->frascos()
            ->utilizables()
            ->when($esperada, fn (Builder $query) => $query->where('dilucion', $esperada['dilucion']))
            ->orderBy('fecha_vencimiento')
            ->first();

        return [
            'frasco_id' => $frasco?->getKey(),
            'esquema_dosis_id' => $esperada['esquema']?->getKey(),
            'volumen_ml' => $esperada['volumen'] ?? null,
            'aplicada_en' => now(),
            'reaccion_local' => ReaccionLocal::Ninguna->value,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function formularioAplicacion(): array
    {
        $inmunoterapia = $this->getOwnerRecord();
        $esperada = $inmunoterapia->dosisEsperada();
        $atraso = $inmunoterapia->diasDeAtraso() ?? 0;
        $regla = $inmunoterapia->reglaDeAtraso();
        $reaccion = $inmunoterapia->ultimaReaccionSistemica();
        $enInicio = $inmunoterapia->fase === FaseInmunoterapia::Inicio;

        return [
            Callout::make("Atrasado {$atraso} días")
                ->description($regla ? "Regla de la doctora: {$regla->accion}" : 'No hay una regla definida para este atraso.')
                ->warning()
                ->visible($atraso > 0),
            Callout::make('Reacción sistémica previa')
                ->description($reaccion ? "{$reaccion->resumen()}." : null)
                ->danger()
                ->visible($reaccion !== null),
            Callout::make('Falta el consentimiento informado firmado')
                ->description('Antes de la primera aplicación, genera el consentimiento en la ficha del paciente (Recetas y consentimientos), imprímelo y registra la firma.')
                ->warning()
                ->visible(! $inmunoterapia->paciente->tieneConsentimientoFirmado(ProcedimientoConsentimiento::Inmunoterapia)),
            Callout::make('Dosis según el esquema')
                ->description($esperada['descripcion'] ?? 'El esquema de inicio está completo. Cambia la fase a mantenimiento para continuar.')
                ->info(),
            Grid::make(2)->components([
                Select::make('frasco_id')
                    ->label('Frasco')
                    ->options(fn (): array => $inmunoterapia->frascos()->utilizables()->orderBy('fecha_vencimiento')->get()
                        ->mapWithKeys(fn (Frasco $frasco): array => [$frasco->getKey() => $frasco->etiqueta()])
                        ->all())
                    ->helperText('Solo aparecen frascos vigentes.')
                    ->required(),
                Select::make('esquema_dosis_id')
                    ->label('Paso del esquema')
                    ->options(fn (): array => $inmunoterapia->esquema->mapWithKeys(fn (EsquemaDosis $paso): array => [$paso->getKey() => $paso->descripcion()])->all())
                    ->visible($enInicio)
                    ->live()
                    ->afterStateUpdated(fn (?string $state, Set $set) => $set('volumen_ml', EsquemaDosis::find($state)?->volumen_ml)),
                TextInput::make('volumen_ml')
                    ->label('Volumen aplicado')
                    ->suffix('mL')
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->maxValue(1)
                    ->required()
                    ->live(onBlur: true),
                DateTimePicker::make('aplicada_en')
                    ->label('Aplicada el')
                    ->native(false)
                    ->seconds(false)
                    ->displayFormat('d/m/Y H:i')
                    ->maxDate(now()->addMinutes(5))
                    ->required(),
            ]),
            Checkbox::make('confirmar_dosis_distinta')
                ->label('Confirmo que esta dosis es distinta a la del esquema')
                ->visible(fn (Get $get): bool => $this->dosisDifiere($get('esquema_dosis_id'), $get('volumen_ml')))
                ->accepted()
                ->validationMessages(['accepted' => 'Confirma la dosis distinta o corrige el volumen.']),
            ToggleButtons::make('brazo')
                ->label('Brazo')
                ->options(Brazo::class)
                ->inline()
                ->required(),
            Grid::make(2)->components([
                Select::make('reaccion_local')
                    ->label('Reacción local')
                    ->options(ReaccionLocal::class)
                    ->required()
                    ->live(),
                TextInput::make('reaccion_local_mm')
                    ->label('Tamaño de la reacción')
                    ->suffix('mm')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->visible(fn (Get $get): bool => filled($get('reaccion_local')) && $get('reaccion_local') !== ReaccionLocal::Ninguna->value && $get('reaccion_local') !== ReaccionLocal::Ninguna),
            ]),
            Textarea::make('notas')
                ->label('Notas')
                ->rows(2),
        ];
    }

    private function dosisDifiere(mixed $esquemaDosisId, mixed $volumen): bool
    {
        if (blank($volumen)) {
            return false;
        }

        $inmunoterapia = $this->getOwnerRecord();
        $esperado = filled($esquemaDosisId)
            ? EsquemaDosis::find($esquemaDosisId)?->volumen_ml
            : ($inmunoterapia->fase === FaseInmunoterapia::Mantenimiento ? $inmunoterapia->dosis_mantenimiento_ml : null);

        return $esperado !== null && abs((float) $esperado - (float) $volumen) > 0.001;
    }

    private function accionTerminarObservacion(): Action
    {
        return Action::make('terminarObservacion')
            ->label('Terminar observación')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Aplicacion $record): bool => ! $record->observacionCompletada() && Gate::allows('update', $record))
            ->disabled(fn (Aplicacion $record): bool => ! $record->puedeTerminarObservacion())
            ->tooltip(fn (Aplicacion $record): ?string => $record->puedeTerminarObservacion() ? null : 'Disponible a las '.$record->observacion_hasta->format('H:i'))
            ->requiresConfirmation()
            ->modalDescription('Confirma que el paciente completó los 30 minutos de observación.')
            ->action(function (Aplicacion $record): void {
                $record->terminarObservacion();
                $fecha = $record->fechaSiguienteSugerida();

                Notification::make()
                    ->title('Observación terminada')
                    ->body('Siguiente aplicación sugerida: '.$fecha->format('d/m/Y').'.')
                    ->success()
                    ->persistent()
                    ->actions([
                        Action::make('agendar')
                            ->label('Agendar siguiente aplicación')
                            ->url(CitaResource::getUrl('create', [
                                'paciente' => $this->getOwnerRecord()->paciente_id,
                                'fecha' => $fecha->toDateString(),
                                'tipo' => TipoCita::Aplicacion->value,
                            ])),
                    ])
                    ->send();
            });
    }

    private function accionReaccionSistemica(): Action
    {
        return Action::make('reaccionSistemica')
            ->label('Reacción sistémica')
            ->icon(Heroicon::OutlinedExclamationTriangle)
            ->color('danger')
            ->visible(fn (Aplicacion $record): bool => $record->reaccionSistemica === null && Gate::allows('create', ReaccionSistemica::class))
            ->modalHeading('Registrar reacción sistémica')
            ->modalDescription('Escala de reacciones sistémicas de la Organización Mundial de Alergia (WAO).')
            ->schema([
                Select::make('grado')
                    ->label('Grado')
                    ->options(GradoWao::class)
                    ->required(),
                TextInput::make('minutos_desde_aplicacion')
                    ->label('Minutos después de la aplicación')
                    ->integer()
                    ->minValue(0)
                    ->maxValue(1440),
                Textarea::make('sintomas')
                    ->label('Síntomas')
                    ->required()
                    ->rows(3),
                Textarea::make('tratamiento')
                    ->label('Tratamiento aplicado')
                    ->rows(2),
                Toggle::make('epinefrina')
                    ->label('Se aplicó epinefrina'),
            ])
            ->action(function (Aplicacion $record, array $data): void {
                $record->reaccionSistemica()->create($data);

                Notification::make()
                    ->title('Reacción sistémica registrada')
                    ->body('Se mostrará en la siguiente aplicación de este paciente.')
                    ->warning()
                    ->send();
            });
    }
}
