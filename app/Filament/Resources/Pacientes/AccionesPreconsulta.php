<?php

namespace App\Filament\Resources\Pacientes;

use App\Enums\RespuestaSiNo;
use App\Filament\Resources\HistoriasClinicas\HistoriaClinicaResource;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Preconsulta;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Acciones de HU-05 que se usan en la ficha del paciente, la agenda y la historia clínica.
 */
class AccionesPreconsulta
{
    /**
     * La recepción (o la doctora) genera el enlace de un solo uso y lo muestra como código QR.
     *
     * @param  Closure(Model): Paciente  $pacienteDe
     * @param  (Closure(Model): ?Cita)|null  $citaDe
     */
    public static function prepararTablet(Closure $pacienteDe, ?Closure $citaDe = null): Action
    {
        return Action::make('prepararTablet')
            ->label('Preparar tablet')
            ->icon(Heroicon::OutlinedDeviceTablet)
            ->color('gray')
            ->visible(fn (): bool => Gate::allows('create', Preconsulta::class))
            ->modalHeading('Pre-consulta en tablet')
            ->modalDescription('Escanea el código con la tablet o abre el enlace en ella. Sirve una sola vez.')
            ->modalWidth(Width::Medium)
            ->fillForm(function (Model $record) use ($pacienteDe, $citaDe): array {
                ['preconsulta' => $preconsulta, 'token' => $token] = Preconsulta::preparar($pacienteDe($record), $citaDe ? $citaDe($record) : null);

                return [
                    'enlace' => Preconsulta::enlace($token),
                    'vence' => $preconsulta->expira_en->format('H:i'),
                ];
            })
            ->schema([
                ViewField::make('enlace')
                    ->hiddenLabel()
                    ->view('filament.preconsulta.codigo-qr'),
                Text::make(fn (Get $get): string => "El enlace vence hoy a las {$get('vence')}. Si se genera otro, este deja de servir.")
                    ->color('gray'),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Listo');
    }

    /**
     * La doctora revisa las respuestas, las corrige y las pasa a la historia clínica.
     *
     * @param  Closure(Model): Paciente  $pacienteDe
     */
    public static function revisar(Closure $pacienteDe): Action
    {
        $preconsultaDe = fn (Model $record): ?Preconsulta => $pacienteDe($record)->preconsultaPorRevisar();

        return Action::make('revisarPreconsulta')
            ->label('Revisar pre-consulta')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('warning')
            ->visible(fn (Model $record): bool => ($preconsulta = $preconsultaDe($record)) !== null && Gate::allows('update', $preconsulta))
            ->slideOver()
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalHeading('Pre-consulta del paciente')
            ->modalDescription(fn (Model $record): string => 'Enviada desde la tablet el '.$preconsultaDe($record)?->enviada_en->format('d/m/Y \a \l\a\s H:i').'. Corrige lo que haga falta antes de pasarlo a la historia clínica.')
            ->fillForm(function (Model $record) use ($preconsultaDe, $pacienteDe): array {
                $preconsulta = $preconsultaDe($record);

                return [
                    'historia' => $preconsulta->propuestaParaHistoria(),
                    'registrar_alergia' => $preconsulta->alergia_medicamentos === RespuestaSiNo::Si,
                    'sustancia_alergia' => $preconsulta->alergia_medicamentos_detalle,
                    'actualizar_celular' => filled($preconsulta->celular) && $preconsulta->celular !== $pacienteDe($record)->celular,
                ];
            })
            ->schema(fn (Model $record): array => self::formularioRevision($preconsultaDe($record), $pacienteDe($record)))
            ->modalSubmitActionLabel('Pasar a la historia clínica')
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('descartar', arguments: ['descartar' => true])
                    ->label('Descartar')
                    ->color('danger'),
            ])
            ->action(function (array $data, array $arguments, Model $record, Action $action) use ($preconsultaDe): void {
                $preconsulta = $preconsultaDe($record);

                if ($preconsulta === null) {
                    return;
                }

                if ($arguments['descartar'] ?? false) {
                    $preconsulta->descartar();

                    Notification::make()->title('Pre-consulta descartada')->warning()->send();

                    return;
                }

                $historia = $preconsulta->pasarAHistoria(Auth::user(), $data);

                Notification::make()
                    ->title('Respuestas pasadas a la historia clínica')
                    ->body('Revisa el resto de los apartados y guarda los cambios.')
                    ->success()
                    ->send();

                $action->redirect(HistoriaClinicaResource::getUrl('edit', ['record' => $historia]));
            });
    }

    /**
     * @return array<int, mixed>
     */
    private static function formularioRevision(?Preconsulta $preconsulta, Paciente $paciente): array
    {
        if ($preconsulta === null) {
            return [];
        }

        return [
            Callout::make('Si un apartado de la historia ya tiene texto, esto se agrega al final con la fecha de la pre-consulta.')
                ->info(),
            Group::make()
                ->statePath('historia')
                ->components([
                    Textarea::make('padecimiento_actual')->label('Padecimiento actual')->rows(3),
                    Textarea::make('antecedentes_heredofamiliares')->label('Antecedentes heredofamiliares')->rows(2),
                    Textarea::make('antecedentes_personales_patologicos')->label('Antecedentes personales patológicos')->rows(3),
                    Textarea::make('antecedentes_personales_no_patologicos')->label('Antecedentes personales no patológicos')->rows(2),
                ]),
            Section::make('Otros datos')
                ->compact()
                ->components([
                    Toggle::make('registrar_alergia')
                        ->label('Registrar la alergia a medicamento como sospechada')
                        ->visible($preconsulta->alergia_medicamentos === RespuestaSiNo::Si)
                        ->live(),
                    TextInput::make('sustancia_alergia')
                        ->label('Medicamento')
                        ->maxLength(120)
                        ->visible(fn (Get $get): bool => (bool) $get('registrar_alergia'))
                        ->required(fn (Get $get): bool => (bool) $get('registrar_alergia')),
                    Toggle::make('actualizar_celular')
                        ->label("Actualizar el celular del paciente a {$preconsulta->celular}")
                        ->helperText('Ahora tiene: '.($paciente->celular ?: 'sin celular'))
                        ->visible(filled($preconsulta->celular) && $preconsulta->celular !== $paciente->celular),
                ]),
        ];
    }
}
