<?php

namespace App\Filament\Resources\Pacientes\RelationManagers;

use App\Enums\ProcedimientoConsentimiento;
use App\Filament\Resources\Pacientes\RelationManagers\Concerns\DescargaDocumentos;
use App\Models\Consentimiento;
use App\Models\Paciente;
use App\Support\Documentos\EmisorDeDocumentos;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * HU-18: consentimiento informado para el prick test y la inmunoterapia.
 * Se genera el PDF para firmar en papel y después se registra la firma.
 *
 * @method Paciente getOwnerRecord()
 */
class ConsentimientosRelationManager extends RelationManager
{
    use DescargaDocumentos;

    protected static string $relationship = 'consentimientos';

    protected static ?string $title = 'Consentimientos';

    protected static ?string $modelLabel = 'consentimiento';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('procedimiento')
                    ->label('Procedimiento')
                    ->options(ProcedimientoConsentimiento::class)
                    ->required()
                    ->columnSpanFull(),
                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->required(),
                TextInput::make('lugar')
                    ->label('Lugar')
                    ->required()
                    ->maxLength(100),
                TextInput::make('firmante_nombre')
                    ->label('Firma')
                    ->helperText('Nombre de quien firma: el paciente o su tutor.')
                    ->required()
                    ->maxLength(150),
                Toggle::make('firmante_es_tutor')
                    ->label('Firma el tutor o representante')
                    ->inline(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('documento'))
            ->recordTitleAttribute('fecha')
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('procedimiento')
                    ->label('Procedimiento'),
                TextColumn::make('firmante_nombre')
                    ->label('Firma')
                    ->description(fn (Consentimiento $record): ?string => $record->firmante_es_tutor ? 'Tutor o representante' : null),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->description(fn (Consentimiento $record): ?string => $record->firmado_en?->format('d/m/Y H:i')),
                TextColumn::make('documento.folio')
                    ->label('Folio'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Generar consentimiento')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->modalHeading('Generar consentimiento informado')
                    ->modalSubmitActionLabel('Generar PDF')
                    ->createAnother(false)
                    ->fillForm(fn (): array => Consentimiento::valoresIniciales($this->getOwnerRecord()))
                    ->using(fn (array $data): Consentimiento => DB::transaction(function () use ($data): Consentimiento {
                        $consentimiento = $this->getOwnerRecord()->consentimientos()->create($data);
                        app(EmisorDeDocumentos::class)->emitirConsentimiento($consentimiento);

                        return $consentimiento;
                    }))
                    ->successNotificationTitle('Consentimiento generado: descárgalo, imprímelo y registra la firma.'),
            ])
            ->recordActions([
                $this->accionDescargarPdf(fn (Consentimiento $record) => $record->documento),
                Action::make('registrarFirma')
                    ->label('Registrar firma')
                    ->icon(Heroicon::OutlinedPencil)
                    ->color('success')
                    ->authorize('firmar')
                    ->requiresConfirmation()
                    ->modalHeading('¿El documento ya está firmado?')
                    ->modalDescription('Confirma que el paciente o su tutor, la doctora y los testigos firmaron el consentimiento impreso. Guarda el papel firmado (o escanéalo como estudio adjunto).')
                    ->modalSubmitActionLabel('Sí, está firmado')
                    ->action(function (Consentimiento $record): void {
                        $record->registrarFirma();

                        Notification::make()->title('Firma registrada')->success()->send();
                    }),
            ]);
    }
}
