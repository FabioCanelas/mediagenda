<?php

namespace App\Models;

use App\Enums\Departamento;
use App\Enums\EstadoAlergia;
use App\Enums\EstadoConsentimiento;
use App\Enums\EstadoPreconsulta;
use App\Enums\ProcedimientoConsentimiento;
use App\Enums\Sexo;
use App\Models\Concerns\RegistraAutor;
use Database\Factories\PacienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'ci', 'ci_complemento', 'ci_expedido', 'nombres', 'apellido_paterno', 'apellido_materno',
    'fecha_nacimiento', 'sexo', 'celular', 'email', 'direccion', 'ocupacion',
    'contacto_emergencia_nombre', 'contacto_emergencia_telefono',
    'tutor_nombre', 'tutor_ci', 'tutor_parentesco',
])]
class Paciente extends Model
{
    /** @use HasFactory<PacienteFactory> */
    use HasFactory, HasUuids, RegistraAutor, SoftDeletes;

    public const EDAD_MAYORIA = 18;

    protected $attributes = [
        'ci_complemento' => '',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'sexo' => Sexo::class,
            'ci_expedido' => Departamento::class,
        ];
    }

    /**
     * @return HasOne<HistoriaClinica, $this>
     */
    public function historiaClinica(): HasOne
    {
        return $this->hasOne(HistoriaClinica::class);
    }

    /**
     * @return HasMany<Alergia, $this>
     */
    public function alergias(): HasMany
    {
        return $this->hasMany(Alergia::class);
    }

    /**
     * Alergias que deben mostrarse en el aviso: todas menos las descartadas.
     *
     * @return HasMany<Alergia, $this>
     */
    public function alergiasVigentes(): HasMany
    {
        return $this->alergias()->where('estado', '!=', EstadoAlergia::Descartada->value);
    }

    /**
     * @return HasMany<PruebaCutanea, $this>
     */
    public function pruebasCutaneas(): HasMany
    {
        return $this->hasMany(PruebaCutanea::class);
    }

    /**
     * @return HasMany<NotaEvolucion, $this>
     */
    public function notasEvolucion(): HasMany
    {
        return $this->hasMany(NotaEvolucion::class);
    }

    /**
     * @return HasMany<Inmunoterapia, $this>
     */
    public function inmunoterapias(): HasMany
    {
        return $this->hasMany(Inmunoterapia::class);
    }

    /**
     * @return HasMany<Cita, $this>
     */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn (): string => trim(implode(' ', array_filter([
            $this->nombres,
            $this->apellido_paterno,
            $this->apellido_materno,
        ]))));
    }

    /**
     * CI con complemento y lugar de expedición, por ejemplo "4567890-1B LP".
     *
     * @return Attribute<string, never>
     */
    protected function ciCompleto(): Attribute
    {
        return Attribute::get(fn (): string => trim(
            $this->ci
            .($this->ci_complemento !== '' && $this->ci_complemento !== null ? '-'.$this->ci_complemento : '')
            .' '.($this->ci_expedido?->value ?? '')
        ));
    }

    /**
     * @return Attribute<int|null, never>
     */
    protected function edad(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->fecha_nacimiento?->age);
    }

    public function esMenorDeEdad(): bool
    {
        return $this->edad !== null && $this->edad < self::EDAD_MAYORIA;
    }

    /**
     * @return HasMany<Adjunto, $this>
     */
    public function adjuntos(): HasMany
    {
        return $this->hasMany(Adjunto::class);
    }

    /**
     * @return HasMany<CuestionarioAct, $this>
     */
    public function cuestionariosAct(): HasMany
    {
        return $this->hasMany(CuestionarioAct::class);
    }

    /**
     * @return HasMany<Consentimiento, $this>
     */
    public function consentimientos(): HasMany
    {
        return $this->hasMany(Consentimiento::class);
    }

    /**
     * @return HasMany<Receta, $this>
     */
    public function recetas(): HasMany
    {
        return $this->hasMany(Receta::class);
    }

    /**
     * @return HasMany<DocumentoEmitido, $this>
     */
    public function documentosEmitidos(): HasMany
    {
        return $this->hasMany(DocumentoEmitido::class);
    }

    /**
     * @return HasMany<Preconsulta, $this>
     */
    public function preconsultas(): HasMany
    {
        return $this->hasMany(Preconsulta::class);
    }

    public function tieneConsentimientoFirmado(ProcedimientoConsentimiento $procedimiento): bool
    {
        return $this->consentimientos()
            ->where('procedimiento', $procedimiento)
            ->where('estado', EstadoConsentimiento::Firmado)
            ->exists();
    }

    /**
     * La pre-consulta más reciente que el paciente envió y la doctora aún no revisó.
     */
    public function preconsultaPorRevisar(): ?Preconsulta
    {
        return $this->preconsultas()
            ->where('estado', EstadoPreconsulta::Enviada)
            ->latest('enviada_en')
            ->first();
    }
}
