<?php

namespace App\Filament\Resources\PruebasCutaneas\Schemas;

use App\Models\Alergeno;
use App\Models\Paciente;
use App\Models\PruebaCutanea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * HU-07: registro del prick test. El resultado de cada alérgeno lo calcula
 * el sistema con el criterio de la doctora (ver App\Models\PruebaCutanea).
 */
class PruebaCutaneaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Paciente y condiciones de la prueba')
                    ->columns(3)
                    ->columnSpanFull()
                    ->components([
                        Select::make('paciente_id')
                            ->label('Paciente')
                            ->relationship(
                                name: 'paciente',
                                titleAttribute: 'nombres',
                                modifyQueryUsing: fn (Builder $query) => $query->orderBy('apellido_paterno'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Paciente $record): string => "{$record->nombre_completo} · CI {$record->ci_completo}")
                            ->searchable(['nombres', 'apellido_paterno', 'apellido_materno', 'ci'])
                            ->default(fn (): ?string => request()->query('paciente'))
                            ->required()
                            ->disabledOn('edit')
                            ->columnSpan(2),
                        DatePicker::make('fecha')
                            ->label('Fecha')
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->maxDate(now())
                            ->required(),
                        Toggle::make('antihistaminicos_suspendidos')
                            ->label('El paciente suspendió los antihistamínicos')
                            ->default(true)
                            ->inline(false),
                        TextInput::make('minutos_lectura')
                            ->label('Lectura a los')
                            ->suffix('minutos')
                            ->integer()
                            ->minValue(10)
                            ->maxValue(60)
                            ->default(15)
                            ->required(),
                        TextInput::make('criterio_positividad_mm')
                            ->label('Criterio de positividad')
                            ->helperText('Diferencia mínima con el control negativo.')
                            ->suffix('mm')
                            ->numeric()
                            ->step(0.5)
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(PruebaCutanea::CRITERIO_POR_DEFECTO_MM)
                            ->required(),
                    ]),
                Section::make('Controles')
                    ->description('Si la histamina no reacciona o la solución salina sí, la prueba no se puede interpretar.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        TextInput::make('control_positivo_mm')
                            ->label('Control positivo (histamina)')
                            ->suffix('mm')
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->maxValue(50)
                            ->required(),
                        TextInput::make('control_negativo_mm')
                            ->label('Control negativo (solución salina)')
                            ->suffix('mm')
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->maxValue(50)
                            ->required(),
                    ]),
                Repeater::make('resultados')
                    ->label('Alérgenos del panel')
                    ->relationship()
                    ->default(fn (): array => Alergeno::query()->delPanel()->get()
                        ->map(fn (Alergeno $alergeno): array => ['alergeno_id' => $alergeno->id, 'papula_mm' => null, 'eritema_mm' => null])
                        ->all())
                    ->columns(3)
                    ->minItems(1)
                    ->reorderable(false)
                    // Una vez guardada la prueba, sus resultados no se quitan (la base no permite borrarlos).
                    ->deletable(fn (string $operation): bool => $operation === 'create')
                    ->addActionLabel('Agregar alérgeno')
                    ->columnSpanFull()
                    ->components([
                        Select::make('alergeno_id')
                            ->label('Alérgeno')
                            ->options(fn (): array => Alergeno::query()->orderBy('orden')->pluck('nombre', 'id')->all())
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->required(),
                        TextInput::make('papula_mm')
                            ->label('Pápula')
                            ->suffix('mm')
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->maxValue(50)
                            ->required(),
                        TextInput::make('eritema_mm')
                            ->label('Eritema')
                            ->suffix('mm')
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->maxValue(100),
                    ]),
                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
