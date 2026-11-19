<?php

namespace App\Filament\Resources\Pacientes\Schemas;

use App\Models\Paciente;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PacienteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ficha de identificación')
                    ->columns(4)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('nombre_completo')
                            ->label('Nombre')
                            ->weight('bold')
                            ->columnSpan(2),
                        TextEntry::make('ci_completo')
                            ->label('CI'),
                        TextEntry::make('sexo')
                            ->label('Sexo'),
                        TextEntry::make('fecha_nacimiento')
                            ->label('Fecha de nacimiento')
                            ->date('d/m/Y'),
                        TextEntry::make('edad')
                            ->label('Edad')
                            ->suffix(' años'),
                        TextEntry::make('ocupacion')
                            ->label('Ocupación')
                            ->placeholder('—'),
                    ]),
                Section::make('Contacto')
                    ->columns(4)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('celular')
                            ->label('Celular')
                            ->placeholder('—'),
                        TextEntry::make('email')
                            ->label('Correo')
                            ->placeholder('—'),
                        TextEntry::make('direccion')
                            ->label('Dirección')
                            ->placeholder('—')
                            ->columnSpan(2),
                        TextEntry::make('contacto_emergencia_nombre')
                            ->label('Contacto de emergencia')
                            ->placeholder('—'),
                        TextEntry::make('contacto_emergencia_telefono')
                            ->label('Teléfono de emergencia')
                            ->placeholder('—'),
                    ]),
                Section::make('Padre, madre o tutor')
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(fn (Paciente $record): bool => $record->esMenorDeEdad() || filled($record->tutor_nombre))
                    ->components([
                        TextEntry::make('tutor_nombre')
                            ->label('Nombre')
                            ->placeholder('—'),
                        TextEntry::make('tutor_ci')
                            ->label('CI')
                            ->placeholder('—'),
                        TextEntry::make('tutor_parentesco')
                            ->label('Parentesco')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
