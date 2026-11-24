<?php

namespace App\Filament\Resources\HistoriasClinicas\Schemas;

use App\Enums\TipoDiagnostico;
use App\Models\Cie10;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * HU-03: historia clínica con los apartados de la NOM-004-SSA3-2012.
 * Los rangos de los signos vitales coinciden con las restricciones CHECK de la base.
 */
class HistoriaClinicaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
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
                    ->unique(table: HistoriaClinica::class, ignoreRecord: true)
                    ->validationMessages(['unique' => 'Este paciente ya tiene historia clínica.'])
                    ->disabledOn('edit')
                    ->columnSpanFull(),
                Tabs::make('Historia clínica')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Interrogatorio')
                            ->components([
                                Textarea::make('padecimiento_actual')
                                    ->label('Padecimiento actual')
                                    ->helperText('Síntomas, desde cuándo, en qué época del año, dónde empeoran y qué los desencadena.')
                                    ->rows(4),
                                Textarea::make('antecedentes_heredofamiliares')
                                    ->label('Antecedentes heredofamiliares')
                                    ->helperText('Familiares con asma, rinitis, dermatitis atópica u otras alergias.')
                                    ->rows(3),
                                Textarea::make('antecedentes_personales_patologicos')
                                    ->label('Antecedentes personales patológicos')
                                    ->helperText('Enfermedades, cirugías, medicamentos actuales, tabaco, alcohol y otras sustancias.')
                                    ->rows(3),
                                Textarea::make('antecedentes_personales_no_patologicos')
                                    ->label('Antecedentes personales no patológicos')
                                    ->helperText('Vivienda (humedad, alfombras, peluches), mascotas, ocupación y alimentación.')
                                    ->rows(3),
                                Textarea::make('interrogatorio_aparatos_sistemas')
                                    ->label('Revisión de otros síntomas, sistema por sistema')
                                    ->helperText('Interrogatorio por aparatos y sistemas: pregunte por cada uno y anote lo que tiene y lo que niega (ojos; nariz y garganta; respiratorio; piel; digestivo; generales).')
                                    ->placeholder("Ej.: Ojos: prurito y lagrimeo matutino.\nRespiratorio: niega tos y sibilancias.\nPiel: niega ronchas o eccema.")
                                    ->rows(7),
                            ]),
                        Tab::make('Exploración física')
                            ->components([
                                Textarea::make('habitus_exterior')
                                    ->label('Habitus exterior')
                                    ->rows(2),
                                Section::make('Signos vitales y somatometría')
                                    ->components([
                                        Grid::make(4)->components([
                                            TextInput::make('presion_sistolica')->label('Presión sistólica')->suffix('mmHg')->integer()->minValue(50)->maxValue(260),
                                            TextInput::make('presion_diastolica')->label('Presión diastólica')->suffix('mmHg')->integer()->minValue(30)->maxValue(160),
                                            TextInput::make('frecuencia_cardiaca')->label('Frecuencia cardiaca')->suffix('lpm')->integer()->minValue(20)->maxValue(250),
                                            TextInput::make('frecuencia_respiratoria')->label('Frecuencia respiratoria')->suffix('rpm')->integer()->minValue(5)->maxValue(80),
                                            TextInput::make('temperatura')->label('Temperatura')->suffix('°C')->numeric()->step(0.1)->minValue(30)->maxValue(45),
                                            TextInput::make('saturacion_oxigeno')->label('Saturación de O₂')->suffix('%')->integer()->minValue(50)->maxValue(100),
                                            TextInput::make('peso_kg')->label('Peso')->suffix('kg')->numeric()->step(0.01)->minValue(0.5)->maxValue(400),
                                            TextInput::make('talla_cm')->label('Talla')->suffix('cm')->numeric()->step(0.1)->minValue(30)->maxValue(250),
                                        ]),
                                    ]),
                                Textarea::make('exploracion_regional')
                                    ->label('Exploración por regiones')
                                    ->helperText('Piel, nariz (rinoscopia), ojos, faringe, tórax y auscultación pulmonar.')
                                    ->rows(4),
                            ]),
                        Tab::make('Diagnóstico y plan')
                            ->components([
                                Textarea::make('resultados_estudios')
                                    ->label('Resultados de estudios')
                                    ->rows(3),
                                Repeater::make('diagnosticos')
                                    ->label('Diagnósticos')
                                    ->relationship()
                                    ->addActionLabel('Agregar diagnóstico')
                                    ->defaultItems(0)
                                    ->columns(3)
                                    ->components([
                                        Select::make('cie10_codigo')
                                            ->label('CIE-10')
                                            ->searchable()
                                            ->getSearchResultsUsing(fn (string $search): array => Cie10::query()
                                                ->where('activo', true)
                                                ->where(fn (Builder $query) => $query
                                                    ->whereLike('codigo', "{$search}%")
                                                    ->orWhereLike('descripcion', "%{$search}%"))
                                                ->orderBy('codigo')
                                                ->limit(30)
                                                ->get()
                                                ->mapWithKeys(fn (Cie10 $cie10): array => [$cie10->codigo => $cie10->etiqueta])
                                                ->all())
                                            ->getOptionLabelUsing(fn (?string $value): ?string => Cie10::find($value)?->etiqueta)
                                            ->live()
                                            ->helperText('Si no está en el catálogo, escríbelo en Descripción.')
                                            ->columnSpan(2),
                                        Select::make('tipo')
                                            ->label('Tipo')
                                            ->options(TipoDiagnostico::class)
                                            ->default(TipoDiagnostico::Principal)
                                            ->required(),
                                        TextInput::make('descripcion')
                                            ->label('Descripción')
                                            ->required(fn (Get $get): bool => blank($get('cie10_codigo')))
                                            ->validationMessages(['required' => 'Elige un código CIE-10 o escribe el diagnóstico.'])
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                    ]),
                                Textarea::make('pronostico')
                                    ->label('Pronóstico')
                                    ->rows(2),
                                Textarea::make('indicacion_terapeutica')
                                    ->label('Indicación terapéutica')
                                    ->helperText('Medicamentos con dosis, vía y frecuencia, y medidas de control ambiental.')
                                    ->rows(4),
                            ]),
                    ]),
            ]);
    }
}
