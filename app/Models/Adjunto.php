<?php

namespace App\Models;

use App\Enums\TipoAdjunto;
use App\Models\Concerns\RegistraAutor;
use Database\Factories\AdjuntoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * Estudio adjunto al expediente (HU-08). Al guardarse toma del archivo real
 * su tipo, su tamaño y su huella SHA-256; el archivo vive en el disco privado.
 */
#[Fillable(['paciente_id', 'tipo', 'fecha_estudio', 'descripcion', 'archivo', 'nombre_original'])]
class Adjunto extends Model
{
    /** @use HasFactory<AdjuntoFactory> */
    use HasFactory, HasUuids, RegistraAutor;

    public const TIPOS_DE_ARCHIVO = ['application/pdf', 'image/jpeg', 'image/png'];

    /**
     * Disco privado donde viven los archivos: local en la computadora y
     * Supabase Storage en la web (ARCHIVOS_DISCO).
     */
    public static function disco(): string
    {
        return (string) config('mediagenda.archivos.disco', 'local');
    }

    protected static function booted(): void
    {
        static::creating(function (Adjunto $adjunto): void {
            $disco = Storage::disk(self::disco());

            $adjunto->mime = $disco->mimeType($adjunto->archivo);
            $adjunto->tamano_bytes = $disco->size($adjunto->archivo);
            $adjunto->sha256 = hash_file('sha256', $disco->path($adjunto->archivo));
            $adjunto->nombre_original ??= basename($adjunto->archivo);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoAdjunto::class,
            'fecha_estudio' => 'date',
            'tamano_bytes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function esImagen(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    /**
     * El archivo sigue en el disco y no cambió desde que se subió.
     */
    public function archivoIntegro(): bool
    {
        $disco = Storage::disk(self::disco());

        return $disco->exists($this->archivo) && hash_equals($this->sha256, hash_file('sha256', $disco->path($this->archivo)));
    }

    public function tamanoLegible(): string
    {
        return Number::fileSize($this->tamano_bytes, precision: 1);
    }
}
