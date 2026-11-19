<?php

namespace App\Filament\Resources\Inmunoterapias\Pages;

use App\Filament\Resources\Inmunoterapias\InmunoterapiaResource;
use App\Filament\Widgets\AvisoAlergias;
use App\Models\Inmunoterapia;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * @property Inmunoterapia $record
 */
class EditInmunoterapia extends EditRecord
{
    protected static string $resource = InmunoterapiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AvisoAlergias::class,
        ];
    }

    /**
     * Un paso del esquema que ya se aplicó no se puede quitar: la base lo impide
     * (llave foránea RESTRICT) y aquí se avisa antes de intentarlo.
     */
    protected function beforeSave(): void
    {
        $idsEnFormulario = collect(array_keys($this->data['esquema'] ?? []))
            ->filter(fn (string $clave): bool => str_starts_with($clave, 'record-'))
            ->map(fn (string $clave): int => (int) substr($clave, strlen('record-')));

        $pasoAplicadoQuitado = $this->record->esquema()
            ->whereNotIn('id', $idsEnFormulario)
            ->whereHas('aplicaciones')
            ->first();

        if ($pasoAplicadoQuitado === null) {
            return;
        }

        Notification::make()
            ->title('No se puede quitar un paso ya aplicado')
            ->body("El paso {$pasoAplicadoQuitado->paso} ya tiene aplicaciones registradas.")
            ->danger()
            ->persistent()
            ->send();

        $this->halt();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
