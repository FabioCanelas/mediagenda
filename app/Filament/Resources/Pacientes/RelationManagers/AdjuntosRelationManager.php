<?php

namespace App\Filament\Resources\Pacientes\RelationManagers;

use App\Enums\TipoAdjunto;
use App\Models\Adjunto;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * HU-08: laboratorios, espirometrías e imágenes del paciente. Solo se aceptan
 * PDF, JPG y PNG; el archivo queda en el disco privado y no se reemplaza.
 */
class AdjuntosRelationManager extends RelationManager
{
    protected static string $relationship = 'adjuntos';

    protected static ?string $title = 'Estudios';

    protected static ?string $modelLabel = 'estudio';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('tipo')
                    ->label('Tipo de estudio')
                    ->options(TipoAdjunto::class)
                    ->required(),
                DatePicker::make('fecha_estudio')
                    ->label('Fecha del estudio')
                    ->default(now())
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->maxDate(now())
                    ->required(),
                TextInput::make('descripcion')
                    ->label('Descripción')
                    ->placeholder('Ej.: IgE total y eosinófilos')
                    ->maxLength(255)
                    ->columnSpanFull(),
                FileUpload::make('archivo')
                    ->label('Archivo')
                    ->helperText('PDF, JPG o PNG de hasta 10 MB.')
                    ->disk(Adjunto::DISCO)
                    ->directory(fn (): string => 'adjuntos/'.$this->getOwnerRecord()->getKey())
                    ->visibility('private')
                    ->acceptedFileTypes(Adjunto::TIPOS_DE_ARCHIVO)
                    ->maxSize(config('mediagenda.adjuntos.tamano_maximo_kb'))
                    ->storeFileNamesIn('nombre_original')
                    ->validationMessages([
                        'mimetypes' => 'Solo se aceptan archivos PDF, JPG o PNG.',
                        'max' => 'El archivo no puede pesar más de 10 MB.',
                    ])
                    ->required()
                    ->hiddenOn('edit')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->defaultSort('fecha_estudio', 'desc')
            ->columns([
                TextColumn::make('fecha_estudio')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->placeholder('—')
                    ->limit(50),
                TextColumn::make('nombre_original')
                    ->label('Archivo')
                    ->icon(fn (Adjunto $record): Heroicon => $record->esImagen() ? Heroicon::OutlinedPhoto : Heroicon::OutlinedDocument),
                TextColumn::make('tamano_bytes')
                    ->label('Tamaño')
                    ->formatStateUsing(fn (Adjunto $record): string => $record->tamanoLegible()),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(TipoAdjunto::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Adjuntar estudio')
                    ->icon(Heroicon::OutlinedPaperClip),
            ])
            ->recordActions([
                Action::make('descargar')
                    ->label('Descargar')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->authorize('view')
                    ->action(fn (Adjunto $record): ?StreamedResponse => $this->descargar($record)),
                EditAction::make()
                    ->label('Corregir datos'),
            ]);
    }

    private function descargar(Adjunto $adjunto): ?StreamedResponse
    {
        if (! $adjunto->archivoIntegro()) {
            Notification::make()
                ->title('El archivo no coincide con el que se subió')
                ->body('Falta en el disco o fue modificado. Revisa las copias de seguridad.')
                ->danger()
                ->send();

            return null;
        }

        return Storage::disk(Adjunto::DISCO)->download($adjunto->archivo, $adjunto->nombre_original);
    }
}
