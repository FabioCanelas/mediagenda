<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Rol;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del usuario')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nombre completo')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('rol')
                            ->label('Rol')
                            ->options(Rol::class)
                            ->default(Rol::Recepcion)
                            ->required()
                            ->helperText('Recepción solo ve pacientes y agenda, nunca la información clínica.')
                            ->disabled(fn (?User $record): bool => self::esElUsuarioActual($record)),
                        Toggle::make('activo')
                            ->label('Puede iniciar sesión')
                            ->default(false)
                            ->inline(false)
                            ->disabled(fn (?User $record): bool => self::esElUsuarioActual($record)),
                    ]),
                Section::make('Contraseña')
                    ->description('Mínimo 12 caracteres, con mayúsculas, minúsculas, números y símbolos.')
                    ->columns(2)
                    ->components([
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->rule(Password::default())
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->confirmed()
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Déjala vacía para no cambiarla.' : null),
                        TextInput::make('password_confirmation')
                            ->label('Repite la contraseña')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false),
                    ]),
            ]);
    }

    /**
     * La doctora no puede quitarse a sí misma el rol ni desactivarse.
     */
    private static function esElUsuarioActual(?User $record): bool
    {
        return $record !== null && $record->is(Auth::user());
    }
}
