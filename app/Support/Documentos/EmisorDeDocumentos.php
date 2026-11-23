<?php

namespace App\Support\Documentos;

use App\Enums\TipoDocumento;
use App\Models\Consentimiento;
use App\Models\DocumentoEmitido;
use App\Models\Paciente;
use App\Models\Receta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Throwable;

/**
 * Genera el PDF con membrete (HU-17 y HU-18), lo guarda en el disco privado y
 * registra la copia con su folio y su huella SHA-256. La reimpresión siempre
 * entrega este mismo archivo, nunca uno nuevo.
 */
class EmisorDeDocumentos
{
    public function emitirReceta(Receta $receta): DocumentoEmitido
    {
        if ($receta->estaEmitida()) {
            throw new LogicException('La receta ya fue emitida; se reimprime la copia guardada.');
        }

        $receta->loadMissing(['medicamentos', 'paciente.alergiasVigentes']);

        if ($receta->medicamentos->isEmpty()) {
            throw new LogicException('La receta no tiene medicamentos.');
        }

        return $this->emitir(TipoDocumento::Receta, $receta->paciente, 'pdf.receta', ['receta' => $receta], ['receta_id' => $receta->getKey()]);
    }

    public function emitirConsentimiento(Consentimiento $consentimiento): DocumentoEmitido
    {
        if ($consentimiento->documento()->exists()) {
            throw new LogicException('El consentimiento ya fue emitido; se reimprime la copia guardada.');
        }

        $consentimiento->loadMissing('paciente');

        return $this->emitir(TipoDocumento::Consentimiento, $consentimiento->paciente, 'pdf.consentimiento', [
            'consentimiento' => $consentimiento,
            'textos' => TextosConsentimiento::para($consentimiento->procedimiento),
        ], ['consentimiento_id' => $consentimiento->getKey()]);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  array<string, string>  $origen
     */
    private function emitir(TipoDocumento $tipo, Paciente $paciente, string $vista, array $datos, array $origen): DocumentoEmitido
    {
        return DB::transaction(function () use ($tipo, $paciente, $vista, $datos, $origen): DocumentoEmitido {
            $emitidoEn = now();
            $folio = DocumentoEmitido::siguienteFolio($tipo, $emitidoEn);
            $ruta = "documentos/{$emitidoEn->year}/{$folio}.pdf";

            $contenido = Pdf::loadView($vista, [
                ...$datos,
                'paciente' => $paciente,
                'folio' => $folio,
                'emitidoEn' => $emitidoEn,
                'consultorio' => config('mediagenda.consultorio'),
            ])
                ->setPaper('letter')
                // Incrusta solo las letras usadas: el PDF pasa de ~880 KB a pocas decenas.
                ->setOption('isFontSubsettingEnabled', true)
                ->output();

            Storage::disk(DocumentoEmitido::disco())->put($ruta, $contenido);

            try {
                return DocumentoEmitido::query()->create([
                    'tipo' => $tipo,
                    'folio' => $folio,
                    'paciente_id' => $paciente->getKey(),
                    ...$origen,
                    'archivo' => $ruta,
                    'sha256' => hash('sha256', $contenido),
                    'tamano_bytes' => strlen($contenido),
                    'emitido_por' => Auth::id(),
                    'emitido_en' => $emitidoEn,
                ]);
            } catch (Throwable $error) {
                Storage::disk(DocumentoEmitido::disco())->delete($ruta);

                throw $error;
            }
        });
    }
}
