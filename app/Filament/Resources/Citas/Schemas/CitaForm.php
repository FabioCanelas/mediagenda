<?php

namespace App\Filament\Resources\Citas\Schemas;

use App\Enums\EstadoCita;
use App\Enums\TipoCita;
use App\Models\Cita;
use App\Models\Paciente;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * HU-14: crear y mover citas. El choque de horario se revisa aquí (con horarios
 * libres sugeridos) y además en la base de datos con un trigger.
 */
class CitaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
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
                            ->disabledOn('edit'),
                        Select::make('tipo')
                            ->label('Tipo de cita')
                            ->options(TipoCita::class)
                            ->default(fn (): string => TipoCita::tryFrom((string) request()->query('tipo'))?->value ?? TipoCita::Control->value)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::ajustarFin($get, $set)),
                        DateTimePicker::make('inicio')
                            ->label('Inicio')
                            ->native(false)
                            ->seconds(false)
                            ->minutesStep(5)
                            ->displayFormat('d/m/Y H:i')
                            ->default(fn (): ?string => self::inicioSugerido())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::ajustarFin($get, $set))
                            ->rules([
                                fn (Get $get, ?Cita $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                                    $mensaje = self::mensajeChoque($value, $get('fin'), $get('tipo'), $record?->getKey());

                                    if ($mensaje !== null) {
                                        $fail($mensaje);
                                    }
                                },
                            ]),
                        DateTimePicker::make('fin')
                            ->label('Fin')
                            ->native(false)
                            ->seconds(false)
                            ->minutesStep(5)
                            ->displayFormat('d/m/Y H:i')
                            ->default(fn (): ?string => self::inicioSugerido(sumarMinutos: TipoCita::tryFrom((string) request()->query('tipo'))?->duracionMinutos() ?? TipoCita::Control->duracionMinutos()))
                            ->after('inicio')
                            ->required(),
                    ]),
                Text::make(fn (Get $get): string => self::textoHorariosLibres($get('inicio'), $get('tipo')))
                    ->color('gray')
                    ->columnSpanFull(),
                Grid::make(2)
                    ->columnSpanFull()
                    ->visibleOn('edit')
                    ->components([
                        Select::make('estado')
                            ->label('Estado')
                            ->options(EstadoCita::class)
                            ->required()
                            ->live(),
                        TextInput::make('motivo_cancelacion')
                            ->label('Motivo de la cancelación')
                            ->maxLength(255)
                            ->required(fn (Get $get): bool => self::esCancelada($get('estado')))
                            ->visible(fn (Get $get): bool => self::esCancelada($get('estado'))),
                    ]),
                Textarea::make('notas')
                    ->label('Notas')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Mensaje de choque con los horarios libres de ese día, o null si el horario está disponible.
     */
    public static function mensajeChoque(mixed $inicio, mixed $fin, mixed $tipo, ?string $ignorarId = null): ?string
    {
        if (blank($inicio) || blank($fin)) {
            return null;
        }

        $inicio = CarbonImmutable::parse($inicio);
        $fin = CarbonImmutable::parse($fin);

        if ($fin->lte($inicio) || ! Cita::seCruza($inicio, $fin, $ignorarId)) {
            return null;
        }

        $libres = Cita::horariosLibres($inicio, (int) $inicio->diffInMinutes($fin), $ignorarId);

        return 'Ese horario se cruza con otra cita.'.($libres === []
            ? ' No quedan horarios libres ese día.'
            : ' Horarios libres: '.collect($libres)->map->format('H:i')->implode(', ').'.');
    }

    private static function ajustarFin(Get $get, Set $set): void
    {
        $tipo = $get('tipo') instanceof TipoCita ? $get('tipo') : TipoCita::tryFrom((string) $get('tipo'));

        if (blank($get('inicio')) || $tipo === null) {
            return;
        }

        $set('fin', CarbonImmutable::parse($get('inicio'))->addMinutes($tipo->duracionMinutos())->format('Y-m-d H:i:s'));
    }

    private static function inicioSugerido(int $sumarMinutos = 0): ?string
    {
        $fecha = request()->query('fecha');

        if (! is_string($fecha) || $fecha === '') {
            return null;
        }

        $tipo = TipoCita::tryFrom((string) request()->query('tipo')) ?? TipoCita::Control;
        $libre = Cita::horariosLibres(CarbonImmutable::parse($fecha), $tipo->duracionMinutos(), maximo: 1)[0] ?? null;

        return $libre?->addMinutes($sumarMinutos)->format('Y-m-d H:i:s');
    }

    private static function textoHorariosLibres(mixed $inicio, mixed $tipo): string
    {
        $tipo = $tipo instanceof TipoCita ? $tipo : TipoCita::tryFrom((string) $tipo);

        if (blank($inicio) || $tipo === null) {
            return 'Elige el día y el tipo de cita para ver los horarios libres.';
        }

        $libres = Cita::horariosLibres(CarbonImmutable::parse($inicio), $tipo->duracionMinutos());

        return $libres === []
            ? 'No quedan horarios libres ese día.'
            : 'Horarios libres ese día: '.collect($libres)->map->format('H:i')->implode(', ').'.';
    }

    private static function esCancelada(mixed $estado): bool
    {
        return ($estado instanceof EstadoCita ? $estado : EstadoCita::tryFrom((string) $estado)) === EstadoCita::Cancelada;
    }
}
