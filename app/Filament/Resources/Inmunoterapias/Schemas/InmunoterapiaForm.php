<?php

namespace App\Filament\Resources\Inmunoterapias\Schemas;

use App\Enums\Dilucion;
use App\Enums\EstadoInmunoterapia;
use App\Enums\FaseInmunoterapia;
use App\Enums\ViaInmunoterapia;
use App\Models\Inmunoterapia;
use App\Models\Paciente;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * HU-09: receta de inmunoterapia con su esquema de inicio.
 * El esquema que se precarga es un ejemplo: la doctora lo ajusta para cada paciente.
 */
class InmunoterapiaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tratamiento')
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
                        DatePicker::make('fecha_inicio')
                            ->label('Fecha de inicio')
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->required(),
                        TextInput::make('extracto')
                            ->label('Extracto')
                            ->placeholder('Laboratorio y nombre del extracto')
                            ->required()
                            ->maxLength(150)
                            ->columnSpan(2),
                        Select::make('via')
                            ->label('Vía')
                            ->options(ViaInmunoterapia::class)
                            ->default(ViaInmunoterapia::Subcutanea)
                            ->required()
                            ->live(),
                        Select::make('alergenos')
                            ->label('Alérgenos')
                            ->relationship('alergenos', 'nombre')
                            ->multiple()
                            ->preload()
                            ->required()
                            ->columnSpan(2),
                        Select::make('fase')
                            ->label('Fase')
                            ->options(FaseInmunoterapia::class)
                            ->default(FaseInmunoterapia::Inicio)
                            ->required(),
                        TextInput::make('dosis_mantenimiento_ml')
                            ->label('Dosis de mantenimiento')
                            ->suffix('mL')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->maxValue(1)
                            ->default(0.5),
                        TextInput::make('intervalo_mantenimiento_dias')
                            ->label('Mantenimiento cada')
                            ->suffix('días')
                            ->integer()
                            ->minValue(7)
                            ->maxValue(90)
                            ->default(28),
                        Select::make('estado')
                            ->label('Estado')
                            ->options(EstadoInmunoterapia::class)
                            ->default(EstadoInmunoterapia::Activa)
                            ->required()
                            ->visibleOn('edit'),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Esquema de la fase de inicio')
                    ->description('Se precarga un esquema convencional de ejemplo (0,05 a 0,50 mL por frasco, una vez por semana). Revísalo y ajústalo para este paciente.')
                    ->visible(fn (Get $get): bool => self::esSubcutanea($get('via')))
                    ->columnSpanFull()
                    ->components([
                        Repeater::make('esquema')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('paso')
                            ->reorderable(false)
                            ->default(fn (): array => Inmunoterapia::esquemaConvencional())
                            ->itemLabel(fn (array $state): string => self::etiquetaPaso($state))
                            ->collapsible()
                            ->collapsed()
                            ->columns(3)
                            ->addActionLabel('Agregar paso')
                            ->components([
                                Select::make('dilucion')
                                    ->label('Frasco')
                                    ->options(Dilucion::class)
                                    ->required()
                                    ->live(),
                                TextInput::make('volumen_ml')
                                    ->label('Volumen')
                                    ->suffix('mL')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0.01)
                                    ->maxValue(1)
                                    ->required()
                                    ->live(onBlur: true),
                                TextInput::make('intervalo_dias')
                                    ->label('Siguiente dosis en')
                                    ->suffix('días')
                                    ->integer()
                                    ->minValue(1)
                                    ->maxValue(60)
                                    ->default(7)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    private static function esSubcutanea(mixed $via): bool
    {
        return ($via instanceof ViaInmunoterapia ? $via : ViaInmunoterapia::tryFrom((string) $via)) === ViaInmunoterapia::Subcutanea;
    }

    /**
     * @param  array<string, mixed>  $estado
     */
    private static function etiquetaPaso(array $estado): string
    {
        $dilucion = $estado['dilucion'] instanceof Dilucion ? $estado['dilucion'] : Dilucion::tryFrom((string) ($estado['dilucion'] ?? ''));

        return trim(($dilucion?->getLabel() ?? 'Frasco sin elegir').' · '.($estado['volumen_ml'] ?? '?').' mL');
    }
}
