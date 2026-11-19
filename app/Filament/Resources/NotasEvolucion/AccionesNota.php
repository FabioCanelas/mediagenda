<?php

namespace App\Filament\Resources\NotasEvolucion;

use App\Models\NotaEvolucion;
use App\Models\NotaEvolucionAnexo;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Acciones de HU-06 que se usan en la vista y en la edición de la nota.
 */
class AccionesNota
{
    public static function cerrar(): Action
    {
        return Action::make('cerrarNota')
            ->label('Cerrar nota')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('¿Cerrar la nota de evolución?')
            ->modalDescription('Una nota cerrada ya no se puede modificar. Si después encuentras un error, podrás agregar un anexo de corrección.')
            ->modalSubmitActionLabel('Cerrar nota')
            ->visible(fn (NotaEvolucion $record): bool => Gate::allows('update', $record))
            ->action(function (NotaEvolucion $record, Action $action): void {
                $record->cerrar(Auth::user());

                Notification::make()->title('Nota cerrada')->success()->send();

                $action->redirect(NotaEvolucionResource::getUrl('view', ['record' => $record]));
            });
    }

    public static function agregarAnexo(): Action
    {
        return Action::make('agregarAnexo')
            ->label('Agregar anexo')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->modalHeading('Anexo de corrección')
            ->modalDescription('Queda registrado con tu nombre, la fecha y la hora. La nota original no cambia.')
            ->schema([
                Textarea::make('texto')
                    ->label('Corrección')
                    ->required()
                    ->rows(4),
            ])
            ->visible(fn (NotaEvolucion $record): bool => $record->estaCerrada() && Gate::allows('create', NotaEvolucionAnexo::class))
            ->action(function (NotaEvolucion $record, array $data): void {
                $record->anexos()->create(['texto' => $data['texto']]);

                Notification::make()->title('Anexo agregado')->success()->send();
            });
    }
}
