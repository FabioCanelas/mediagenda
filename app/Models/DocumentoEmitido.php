<?php

namespace App\Models;

use App\Enums\TipoDocumento;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use LogicException;

/**
 * Copia de un documento entregado al paciente (HU-17 y HU-18). Solo se inserta:
 * la base lo impide con triggers y el modelo también se niega a modificarlo.
 * No tiene factory: se crea siempre con EmisorDeDocumentos, que genera el PDF real.
 */
#[Table(name: 'documentos_emitidos', timestamps: false)]
#[Fillable([
    'tipo', 'folio', 'paciente_id', 'receta_id', 'consentimiento_id',
    'archivo', 'sha256', 'tamano_bytes', 'emitido_por', 'emitido_en',
])]
class DocumentoEmitido extends Model
{
    use HasUuids;

    /**
     * Mismo disco privado que los estudios adjuntos (ARCHIVOS_DISCO).
     */
    public static function disco(): string
    {
        return Adjunto::disco();
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Un documento emitido no se puede modificar.'));
        static::deleting(fn (): never => throw new LogicException('Un documento emitido no se puede borrar.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoDocumento::class,
            'tamano_bytes' => 'integer',
            'emitido_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    /**
     * @return BelongsTo<Receta, $this>
     */
    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }

    /**
     * @return BelongsTo<Consentimiento, $this>
     */
    public function consentimiento(): BelongsTo
    {
        return $this->belongsTo(Consentimiento::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function emitidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emitido_por');
    }

    /**
     * Folio correlativo por tipo y año: R-2026-000001. Se calcula dentro de la
     * transacción de emisión; la llave única de la base evita duplicados.
     */
    public static function siguienteFolio(TipoDocumento $tipo, CarbonInterface $fecha): string
    {
        $prefijo = "{$tipo->prefijo()}-{$fecha->year}-";

        // PostgreSQL no admite FOR UPDATE junto a max(): se bloquea la última fila y se lee su folio.
        $ultimo = static::query()
            ->whereLike('folio', "{$prefijo}%", caseSensitive: true)
            ->orderByDesc('folio')
            ->lockForUpdate()
            ->value('folio');

        $numero = $ultimo === null ? 1 : ((int) substr($ultimo, strlen($prefijo))) + 1;

        return $prefijo.str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
    }

    /**
     * El PDF guardado es exactamente el que se entregó.
     */
    public function archivoIntegro(): bool
    {
        $disco = Storage::disk(self::disco());

        return $disco->exists($this->archivo) && hash_equals($this->sha256, hash('sha256', (string) $disco->get($this->archivo)));
    }

    public function nombreDescarga(): string
    {
        return "{$this->folio}.pdf";
    }
}
