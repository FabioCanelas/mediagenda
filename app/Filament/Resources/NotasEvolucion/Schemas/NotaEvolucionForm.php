<?php

namespace App\Filament\Resources\NotasEvolucion\Schemas;

use App\Models\Paciente;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * HU-06: nota de evolución con los campos del apartado 6.2 de la NOM-004.
 */
class NotaEvolucionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
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
                    ]),
                Textarea::make('evolucion')
                    ->label('Evolución y actualización del cuadro clínico')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Section::make('Signos vitales')
                    ->description('Solo los que hagan falta en esta consulta.')
                    ->collapsible()
                    ->columnSpanFull()
                    ->components([
                        Grid::make(4)->components([
                            TextInput::make('presion_sistolica')->label('Presión sistólica')->suffix('mmHg')->integer()->minValue(50)->maxValue(260),
                            TextInput::make('presion_diastolica')->label('Presión diastólica')->suffix('mmHg')->integer()->minValue(30)->maxValue(160),
                            TextInput::make('frecuencia_cardiaca')->label('Frecuencia cardiaca')->suffix('lpm')->integer()->minValue(20)->maxValue(250),
                            TextInput::make('frecuencia_respiratoria')->label('Frecuencia respiratoria')->suffix('rpm')->integer()->minValue(5)->maxValue(80),
                            TextInput::make('temperatura')->label('Temperatura')->suffix('°C')->numeric()->step(0.1)->minValue(30)->maxValue(45),
                            TextInput::make('saturacion_oxigeno')->label('Saturación de O₂')->suffix('%')->integer()->minValue(50)->maxValue(100),
                            TextInput::make('peso_kg')->label('Peso')->suffix('kg')->numeric()->step(0.01)->minValue(0.5)->maxValue(400),
                        ]),
                    ]),
                Textarea::make('resultados_estudios')
                    ->label('Resultados relevantes de estudios')
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('diagnosticos')
                    ->label('Diagnósticos o problemas clínicos')
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('pronostico')
                    ->label('Pronóstico')
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('tratamiento')
                    ->label('Tratamiento e indicaciones')
                    ->helperText('Medicamentos con dosis, vía y periodicidad.')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
