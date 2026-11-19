<?php

namespace App\Filament\Resources\Pacientes\RelationManagers;

use App\Enums\TipoDocumento;
use App\Filament\Resources\Pacientes\RelationManagers\Concerns\DescargaDocumentos;
use App\Models\DocumentoEmitido;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * HU-17: todos los documentos entregados al paciente, para reimprimir la copia.
 */
class DocumentosEmitidosRelationManager extends RelationManager
{
    use DescargaDocumentos;

    protected static string $relationship = 'documentosEmitidos';

    protected static ?string $title = 'Documentos emitidos';

    protected static ?string $modelLabel = 'documento';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('emitidoPor'))
            ->recordTitleAttribute('folio')
            ->defaultSort('emitido_en', 'desc')
            ->columns([
                TextColumn::make('folio')
                    ->label('Folio')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('tipo')
                    ->label('Documento'),
                TextColumn::make('emitido_en')
                    ->label('Emitido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('emitidoPor.name')
                    ->label('Por')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Documento')
                    ->options(TipoDocumento::class),
            ])
            ->recordActions([
                $this->accionDescargarPdf(fn (DocumentoEmitido $record): DocumentoEmitido => $record),
            ]);
    }
}
