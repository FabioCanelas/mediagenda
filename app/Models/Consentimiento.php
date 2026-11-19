<?php

namespace App\Models;

use App\Enums\EstadoConsentimiento;
use App\Enums\ProcedimientoConsentimiento;
use App\Models\Concerns\RegistraAutor;
use Database\Factories\ConsentimientoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Consentimiento informado (HU-18). Se emite en PDF para que el paciente
 * (o su tutor) lo firme; después solo se registra la firma.
 */
#[Fillable(['paciente_id', 'procedimiento', 'fecha', 'lugar', 'firmante_nombre', 'firmante_es_tutor'])]
class Consentimiento extends Model
{
    /** @use HasFactory<ConsentimientoFactory> */
    use HasFactory, HasUuids, RegistraAutor;

    protected $attributes = [
        'estado' => 'pendiente',
        'firmante_es_tutor' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'procedimiento' => ProcedimientoConsentimiento::class,
            'estado' => EstadoConsentimiento::class,
            'fecha' => 'date',
            'firmante_es_tutor' => 'boolean',
            'firmado_en' => 'datetime',
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
     * @return HasOne<DocumentoEmitido, $this>
     */
    public function documento(): HasOne
    {
        return $this->hasOne(DocumentoEmitido::class);
    }

    public function estaFirmado(): bool
    {
        return $this->estado === EstadoConsentimiento::Firmado;
    }

    public function registrarFirma(): void
    {
        $this->forceFill([
            'estado' => EstadoConsentimiento::Firmado,
            'firmado_en' => now(),
        ])->save();
    }

    /**
     * Datos para precargar el formulario: si el paciente es menor, firma su tutor.
     *
     * @return array<string, mixed>
     */
    public static function valoresIniciales(Paciente $paciente): array
    {
        $firmaTutor = $paciente->esMenorDeEdad() && filled($paciente->tutor_nombre);

        return [
            'fecha' => now()->toDateString(),
            'lugar' => config('mediagenda.consultorio.ciudad'),
            'firmante_nombre' => $firmaTutor ? $paciente->tutor_nombre : $paciente->nombre_completo,
            'firmante_es_tutor' => $firmaTutor,
        ];
    }
}
