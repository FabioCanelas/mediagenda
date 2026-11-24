<?php

namespace App\Filament\Resources\Pacientes\Schemas;

use App\Enums\Departamento;
use App\Enums\Sexo;
use App\Models\Paciente;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class PacienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ficha de identificación')
                    ->columns(3)
                    ->columnSpanFull()
                    ->components([
                        TextInput::make('ci')
                            ->label('CI')
                            ->required()
                            ->regex('/^[0-9]{5,12}$/')
                            ->maxLength(12)
                            // Al editar, impide cambiar el CI por el de otro paciente.
                            // Al crear, el duplicado se revisa en CreatePaciente para mostrar su expediente.
                            ->rules(fn (string $operation, Get $get, ?Paciente $record): array => $operation === 'edit'
                                ? [Rule::unique('pacientes', 'ci')->ignore($record)->where('ci_complemento', mb_strtoupper(trim((string) $get('ci_complemento'))))]
                                : [])
                            ->validationMessages([
                                'regex' => 'El CI debe tener solo números, entre 5 y 12 dígitos.',
                                'unique' => 'Ya existe otro paciente con este CI.',
                            ]),
                        TextInput::make('ci_complemento')
                            ->label('Complemento')
                            ->helperText('Solo si el CI lo tiene, por ejemplo 1B.')
                            ->maxLength(5)
                            ->alphaNum()
                            ->dehydrateStateUsing(fn (?string $state): string => mb_strtoupper(trim((string) $state))),
                        Select::make('ci_expedido')
                            ->label('Expedido en')
                            ->options(Departamento::class),
                        TextInput::make('nombres')
                            ->label('Nombres')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('apellido_paterno')
                            ->label('Apellido paterno')
                            ->maxLength(60)
                            ->requiredWithout('apellido_materno')
                            ->validationMessages(['required_without' => 'Registra al menos un apellido.']),
                        TextInput::make('apellido_materno')
                            ->label('Apellido materno')
                            ->maxLength(60),
                        DatePicker::make('fecha_nacimiento')
                            ->label('Fecha de nacimiento')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate('1900-01-01')
                            ->maxDate(now())
                            ->live(onBlur: true),
                        Select::make('sexo')
                            ->label('Sexo')
                            ->options(Sexo::class)
                            ->required(),
                        TextInput::make('celular')
                            ->label('Celular')
                            ->tel()
                            ->maxLength(20),
                    ]),
                // Lo que no hace falta para atender hoy: plegado al registrar, abierto al editar.
                Section::make('Más datos (opcional)')
                    ->description('Ocupación, correo, dirección y contacto de emergencia.')
                    ->collapsible()
                    ->collapsed(fn (string $operation): bool => $operation === 'create')
                    ->columns(3)
                    ->columnSpanFull()
                    ->components([
                        TextInput::make('ocupacion')
                            ->label('Ocupación')
                            ->maxLength(80),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->maxLength(120),
                        TextInput::make('direccion')
                            ->label('Dirección')
                            ->maxLength(200),
                        TextInput::make('contacto_emergencia_nombre')
                            ->label('Contacto de emergencia')
                            ->maxLength(120),
                        TextInput::make('contacto_emergencia_telefono')
                            ->label('Teléfono de emergencia')
                            ->tel()
                            ->maxLength(20),
                    ]),
                Section::make('Padre, madre o tutor')
                    ->description('Obligatorio para menores de 18 años.')
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => self::esMenor($get('fecha_nacimiento')))
                    ->components([
                        TextInput::make('tutor_nombre')
                            ->label('Nombre completo')
                            ->required(fn (Get $get): bool => self::esMenor($get('fecha_nacimiento')))
                            ->maxLength(120),
                        TextInput::make('tutor_ci')
                            ->label('CI del tutor')
                            ->required(fn (Get $get): bool => self::esMenor($get('fecha_nacimiento')))
                            ->maxLength(20),
                        TextInput::make('tutor_parentesco')
                            ->label('Parentesco')
                            ->placeholder('Madre, padre, tutor legal…')
                            ->required(fn (Get $get): bool => self::esMenor($get('fecha_nacimiento')))
                            ->maxLength(40),
                    ]),
            ]);
    }

    public static function esMenor(mixed $fechaNacimiento): bool
    {
        if (blank($fechaNacimiento)) {
            return false;
        }

        return CarbonImmutable::parse($fechaNacimiento)->age < Paciente::EDAD_MAYORIA;
    }
}
