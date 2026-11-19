<?php

namespace App\Filament\Preconsulta;

use App\Enums\RespuestaSiNo;
use App\Models\Preconsulta;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Exposed;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;

/**
 * HU-05: formulario que el paciente llena en la tablet de la sala de espera.
 *
 * Es una página pública: no pide usuario, solo un enlace de un solo uso que vence.
 * Si el navegador de la tablet tenía abierta una sesión del personal, se cierra,
 * para que el paciente no pueda llegar al expediente.
 */
class FormularioPreconsulta extends SimplePage
{
    use WithRateLimiting;

    protected Width|string|null $maxWidth = Width::ThreeExtraLarge;

    /**
     * formulario | enviada | invalida
     */
    #[Locked]
    public string $etapa = 'formulario';

    #[Locked]
    public ?string $preconsultaId = null;

    #[Locked]
    public ?string $nombrePaciente = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(string $token): void
    {
        if (Auth::check()) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
        }

        $preconsulta = Preconsulta::vigentePorToken($token);

        if ($preconsulta === null) {
            $this->etapa = 'invalida';

            return;
        }

        $this->preconsultaId = $preconsulta->getKey();
        $this->nombrePaciente = $preconsulta->paciente->nombres;
        $this->form->fill(['celular' => $preconsulta->celular]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Pre-consulta';
    }

    public function getHeading(): string|Htmlable|null
    {
        return match ($this->etapa) {
            'formulario' => "Hola, {$this->nombrePaciente}",
            'enviada' => '¡Gracias!',
            default => 'Este enlace ya no sirve',
        };
    }

    public function getSubheading(): string|Htmlable|null
    {
        return match ($this->etapa) {
            'formulario' => 'Responda estas preguntas antes de pasar a su consulta. Solo la doctora verá sus respuestas.',
            'enviada' => 'Sus respuestas llegaron a la doctora. Devuelva la tablet en recepción.',
            default => 'Ya se usó o venció. Pida en recepción que preparen la tablet otra vez.',
        };
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Motivo de la consulta')
                    ->components([
                        Textarea::make('motivo_consulta')
                            ->label('¿Por qué viene a la consulta?')
                            ->rows(3)
                            ->maxLength(2000)
                            ->required(),
                        TextInput::make('tiempo_evolucion')
                            ->label('¿Desde cuándo tiene estas molestias?')
                            ->placeholder('Ej.: hace 6 meses')
                            ->maxLength(100)
                            ->required(),
                        CheckboxList::make('sintomas')
                            ->label('Marque lo que le pasa')
                            ->options(Preconsulta::SINTOMAS),
                    ]),
                Section::make('Alergias y medicamentos')
                    ->components([
                        Radio::make('alergia_medicamentos')
                            ->label('¿Es alérgico a algún medicamento?')
                            ->options(RespuestaSiNo::class)
                            ->inline()
                            ->required()
                            ->live(),
                        TextInput::make('alergia_medicamentos_detalle')
                            ->label('¿A cuál medicamento?')
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => self::esSi($get('alergia_medicamentos')))
                            ->required(fn (Get $get): bool => self::esSi($get('alergia_medicamentos'))),
                        Textarea::make('otras_alergias')
                            ->label('¿Tiene alergia a algún alimento, picadura u otra cosa?')
                            ->rows(2)
                            ->maxLength(1000),
                        Textarea::make('medicamentos_actuales')
                            ->label('¿Qué medicamentos usa ahora?')
                            ->rows(2)
                            ->maxLength(1000),
                    ]),
                Section::make('Su familia y su casa')
                    ->components([
                        Textarea::make('antecedentes_familiares')
                            ->label('¿Alguien de su familia tiene asma, rinitis, alergias o dermatitis?')
                            ->rows(2)
                            ->maxLength(1000),
                        TextInput::make('mascotas')
                            ->label('¿Tiene mascotas en casa? ¿Cuáles?')
                            ->maxLength(255),
                        Radio::make('fuma')
                            ->label('¿Fuma?')
                            ->boolean('Sí', 'No')
                            ->inline()
                            ->required(),
                    ]),
                Section::make('Sus datos de contacto')
                    ->components([
                        TextInput::make('celular')
                            ->label('Confirme su celular')
                            ->tel()
                            ->maxLength(20)
                            ->required(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('enviar')
                    ->footer([
                        Actions::make([
                            Action::make('enviar')
                                ->label('Enviar respuestas')
                                ->size('xl')
                                ->submit('enviar'),
                        ])->fullWidth(),
                    ])
                    ->visible(fn (): bool => $this->etapa === 'formulario'),
                Callout::make('Ya puede cerrar esta pantalla.')
                    ->success()
                    ->visible(fn (): bool => $this->etapa === 'enviada'),
                Callout::make('Por seguridad, cada enlace sirve una sola vez.')
                    ->warning()
                    ->visible(fn (): bool => $this->etapa === 'invalida'),
            ]);
    }

    #[Exposed]
    public function enviar(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title("Demasiados intentos. Espere {$exception->secondsUntilAvailable} segundos.")
                ->danger()
                ->send();

            return;
        }

        $preconsulta = Preconsulta::query()->find($this->preconsultaId);

        if ($preconsulta === null || ! $preconsulta->estaVigente()) {
            $this->etapa = 'invalida';

            return;
        }

        $respuestas = $this->form->getState();

        if (! self::esSi($respuestas['alergia_medicamentos'] ?? null)) {
            $respuestas['alergia_medicamentos_detalle'] = null;
        }

        $preconsulta->enviar($respuestas);

        $this->etapa = 'enviada';
        $this->data = [];
    }

    private static function esSi(mixed $respuesta): bool
    {
        return $respuesta === RespuestaSiNo::Si || $respuesta === RespuestaSiNo::Si->value;
    }
}
