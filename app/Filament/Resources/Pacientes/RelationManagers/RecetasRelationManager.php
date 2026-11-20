<?php

namespace App\Filament\Resources\Pacientes\RelationManagers;

use App\Filament\Resources\Pacientes\RelationManagers\Concerns\DescargaDocumentos;
use App\Models\Paciente;
use App\Models\Receta;
use App\Models\RecetaMedicamento;
use App\Support\Documentos\EmisorDeDocumentos;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * HU-17: recetas e indicaciones con membrete. Se corrigen mientras son borrador;
 * al emitirlas se genera el PDF y se guarda la copia para reimprimirla.
 *
 * @method Paciente getOwnerRecord()
 */
class RecetasRelationManager extends RelationManager
{
    use DescargaDocumentos;

    protected static string $relationship = 'recetas';

    protected static ?string $title = 'Recetas';

    protected static ?string $modelLabel = 'receta';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        $alergias = $this->getOwnerRecord()->alergiasVigentes()->pluck('sustancia');

        return $schema
            ->components([
                Callout::make('Alergias del paciente: '.$alergias->implode(', '))
                    ->description('Saldrán impresas en la receta.')
                    ->danger()
                    ->visible($alergias->isNotEmpty()),
                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->default(now())
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->maxDate(now())
                    ->required(),
                Repeater::make('medicamentos')
                    ->label('Medicamentos')
                    ->relationship()
                    ->orderColumn('orden')
                    ->reorderable(false)
                    ->defaultItems(1)
                    ->minItems(1)
                    ->addActionLabel('Agregar medicamento')
                    ->itemLabel(fn (array $state): ?string => $state['medicamento'] ?? null)
                    ->columns(2)
                    ->schema([
                        TextInput::make('medicamento')
                            ->label('Medicamento')
                            ->placeholder('Ej.: Loratadina')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('presentacion')
                            ->label('Presentación')
                            ->placeholder('Ej.: 10 mg tabletas')
                            ->maxLength(100),
                        TextInput::make('dosis')
                            ->label('Dosis')
                            ->placeholder('Ej.: 1 tableta cada 24 horas')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('duracion')
                            ->label('Duración')
                            ->placeholder('Ej.: por 30 días')
                            ->maxLength(100),
                        TextInput::make('indicaciones')
                            ->label('Indicaciones')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Textarea::make('indicaciones')
                    ->label('Indicaciones generales')
                    ->placeholder('Medidas de control ambiental, signos de alarma, próxima cita…')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['medicamentos', 'documento']))
            ->recordTitleAttribute('fecha')
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('medicamentos')
                    ->label('Medicamentos')
                    ->state(fn (Receta $record): string => $record->medicamentos->map(fn (RecetaMedicamento $medicamento): string => $medicamento->medicamento)->implode(', '))
                    ->wrap(),
                TextColumn::make('documento.folio')
                    ->label('Folio')
                    ->placeholder('Borrador')
                    ->badge()
                    ->color(fn (?string $state): string => $state === null ? 'warning' : 'success'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva receta')
                    ->modalHeading('Nueva receta')
                    ->modalSubmitActionLabel('Guardar borrador')
                    ->createAnother(false)
                    ->icon(Heroicon::OutlinedPlus),
            ])
            ->recordActions([
                Action::make('emitir')
                    ->label('Emitir PDF')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->authorize('emitir')
                    ->requiresConfirmation()
                    ->modalHeading('¿Emitir la receta?')
                    ->modalDescription('Se genera el PDF con tu membrete y se guarda una copia. Después ya no se podrá modificar.')
                    ->modalSubmitActionLabel('Emitir y descargar')
                    ->action(fn (Receta $record): ?StreamedResponse => $this->emitir($record)),
                $this->accionDescargarPdf(fn (Receta $record) => $record->documento),
                ViewAction::make()
                    ->modalHeading('Receta'),
                EditAction::make()
                    ->modalHeading('Corregir receta'),
            ]);
    }

    private function emitir(Receta $receta): ?StreamedResponse
    {
        $documento = app(EmisorDeDocumentos::class)->emitirReceta($receta);

        Notification::make()
            ->title("Receta emitida con el folio {$documento->folio}")
            ->body('La copia quedó guardada en el expediente.')
            ->success()
            ->send();

        return $this->descargarDocumento($documento);
    }
}
