<?php

namespace App\Filament\Resources\Pacientes\RelationManagers\Concerns;

use App\Models\DocumentoEmitido;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reimpresión de documentos emitidos (HU-17): entrega la copia guardada,
 * después de comprobar que su huella SHA-256 no cambió.
 */
trait DescargaDocumentos
{
    /**
     * @param  callable(Model): ?DocumentoEmitido  $documentoDe
     */
    protected function accionDescargarPdf(callable $documentoDe): Action
    {
        return Action::make('descargarPdf')
            ->label('Descargar PDF')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->visible(fn (Model $record): bool => ($documento = $documentoDe($record)) !== null && Gate::allows('view', $documento))
            ->action(fn (Model $record): ?StreamedResponse => $this->descargarDocumento($documentoDe($record)));
    }

    protected function descargarDocumento(?DocumentoEmitido $documento): ?StreamedResponse
    {
        if ($documento === null) {
            return null;
        }

        if (! $documento->archivoIntegro()) {
            Notification::make()
                ->title('La copia del documento no coincide con la emitida')
                ->body("El archivo del folio {$documento->folio} falta o fue modificado. Revisa las copias de seguridad.")
                ->danger()
                ->send();

            return null;
        }

        return Storage::disk(DocumentoEmitido::disco())->download($documento->archivo, $documento->nombreDescarga());
    }
}
