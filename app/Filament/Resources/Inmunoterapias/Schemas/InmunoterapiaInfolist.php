<?php

namespace App\Filament\Resources\Inmunoterapias\Schemas;

use App\Models\Inmunoterapia;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InmunoterapiaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Callout::make(fn (Inmunoterapia $record): string => self::tituloAtraso($record))
                    ->description(fn (Inmunoterapia $record): string => $record->reglaDeAtraso()?->accion
                        ? 'Regla de la doctora: '.$record->reglaDeAtraso()->accion
                        : 'No hay una regla definida para este atraso. Revisa las reglas en Configuración.')
                    ->warning()
                    ->visible(fn (Inmunoterapia $record): bool => ($record->diasDeAtraso() ?? 0) > 0)
                    ->columnSpanFull(),
                Callout::make('Reacción sistémica previa')
                    ->description(fn (Inmunoterapia $record): ?string => ($reaccion = $record->ultimaReaccionSistemica())
                        ? "{$reaccion->resumen()}: {$reaccion->sintomas}"
                        : null)
                    ->danger()
                    ->visible(fn (Inmunoterapia $record): bool => $record->ultimaReaccionSistemica() !== null)
                    ->columnSpanFull(),
                Section::make('Tratamiento')
                    ->columns(4)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('paciente.nombre_completo')->label('Paciente')->weight('bold')->columnSpan(2),
                        TextEntry::make('fase')->label('Fase')->badge(),
                        TextEntry::make('estado')->label('Estado')->badge(),
                        TextEntry::make('extracto')->label('Extracto')->columnSpan(2),
                        TextEntry::make('via')->label('Vía'),
                        TextEntry::make('fecha_inicio')->label('Inicio')->date('d/m/Y'),
                        TextEntry::make('alergenos.nombre')->label('Alérgenos')->badge()->color('gray')->columnSpan(2),
                        TextEntry::make('mantenimiento')
                            ->label('Mantenimiento')
                            ->state(fn (Inmunoterapia $record): ?string => $record->dosis_mantenimiento_ml
                                ? "{$record->dosis_mantenimiento_ml} mL cada {$record->intervalo_mantenimiento_dias} días"
                                : null)
                            ->placeholder('Sin definir')
                            ->columnSpan(2),
                    ]),
                Section::make('Próxima dosis')
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(fn (Inmunoterapia $record): bool => $record->admiteAplicaciones())
                    ->components([
                        TextEntry::make('dosis_esperada')
                            ->label('Según el esquema')
                            ->state(fn (Inmunoterapia $record): ?string => $record->dosisEsperada()['descripcion'] ?? null)
                            ->placeholder('El esquema de inicio está completo: pasa la fase a mantenimiento.')
                            ->columnSpan(2),
                        TextEntry::make('proximaAplicacion.fecha_prevista')
                            ->label('Fecha prevista')
                            ->date('d/m/Y')
                            ->placeholder('—'),
                    ]),
                Section::make('Esquema de la fase de inicio')
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->visible(fn (Inmunoterapia $record): bool => $record->esquema()->exists())
                    ->components([
                        RepeatableEntry::make('esquema')
                            ->hiddenLabel()
                            ->columns(4)
                            ->components([
                                TextEntry::make('paso')->label('Paso'),
                                TextEntry::make('dilucion')->label('Frasco')->badge(),
                                TextEntry::make('volumen_ml')->label('Volumen')->suffix(' mL'),
                                TextEntry::make('intervalo_dias')->label('Siguiente dosis en')->suffix(' días'),
                            ]),
                    ]),
            ]);
    }

    private static function tituloAtraso(Inmunoterapia $inmunoterapia): string
    {
        $dias = $inmunoterapia->diasDeAtraso() ?? 0;

        return "Atrasado {$dias} ".($dias === 1 ? 'día' : 'días');
    }
}
