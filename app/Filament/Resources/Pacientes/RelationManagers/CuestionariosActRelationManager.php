<?php

namespace App\Filament\Resources\Pacientes\RelationManagers;

use App\Enums\ControlAsma;
use App\Models\CuestionarioAct;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * HU-16: cuestionario de control del asma. La doctora anota las 5 respuestas
 * del formulario oficial y el sistema calcula el puntaje (5 a 25).
 */
class CuestionariosActRelationManager extends RelationManager
{
    protected static string $relationship = 'cuestionariosAct';

    protected static ?string $title = 'Control del asma (ACT)';

    protected static ?string $modelLabel = 'cuestionario ACT';

    protected static ?string $pluralModelLabel = 'cuestionarios ACT';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        $preguntas = collect(CuestionarioAct::PREGUNTAS)->map(fn (array $pregunta, int $numero): Radio => Radio::make("respuesta_{$numero}")
            ->label("{$numero}. {$pregunta['titulo']}")
            ->helperText($pregunta['pregunta'])
            ->options(collect($pregunta['opciones'])->mapWithKeys(fn (string $opcion, int $puntos): array => [$puntos => "{$opcion} ({$puntos})"])->all())
            ->inline()
            ->required()
            ->live());

        return $schema
            ->components([
                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->default(now())
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->maxDate(now())
                    ->required(),
                Callout::make('Responde pensando en las últimas 4 semanas.')
                    ->info(),
                ...$preguntas->values()->all(),
                Callout::make(fn (Get $get): string => self::textoPuntaje($get))
                    ->color(fn (Get $get): string => self::colorPuntaje($get)),
                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('fecha')
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('puntaje')
                    ->label('Puntaje')
                    ->state(fn (CuestionarioAct $record): string => "{$record->puntajeTotal()} / 25")
                    ->weight('medium'),
                TextColumn::make('control')
                    ->label('Control')
                    ->badge()
                    ->state(fn (CuestionarioAct $record): ControlAsma => $record->control()),
                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->placeholder('—')
                    ->limit(60),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar ACT')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    /**
     * @return array<int, int>|null Las 5 respuestas, o null si falta alguna.
     */
    private static function respuestas(Get $get): ?array
    {
        $respuestas = collect(array_keys(CuestionarioAct::PREGUNTAS))->map(fn (int $numero): mixed => $get("respuesta_{$numero}"));

        return $respuestas->contains(fn (mixed $respuesta): bool => blank($respuesta)) ? null : $respuestas->map(fn (mixed $respuesta): int => (int) $respuesta)->all();
    }

    private static function textoPuntaje(Get $get): string
    {
        $respuestas = self::respuestas($get);

        if ($respuestas === null) {
            return 'El puntaje aparece al responder las 5 preguntas.';
        }

        $puntaje = array_sum($respuestas);

        return "Puntaje: {$puntaje} de 25 · ".ControlAsma::segunPuntaje($puntaje)->getLabel();
    }

    private static function colorPuntaje(Get $get): string
    {
        $respuestas = self::respuestas($get);

        return $respuestas === null ? 'gray' : ControlAsma::segunPuntaje(array_sum($respuestas))->getColor();
    }
}
