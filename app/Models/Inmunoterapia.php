<?php

namespace App\Models;

use App\Enums\Dilucion;
use App\Enums\EstadoInmunoterapia;
use App\Enums\FaseInmunoterapia;
use App\Enums\ViaInmunoterapia;
use App\Models\Concerns\RegistraAutor;
use Database\Factories\InmunoterapiaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Receta de inmunoterapia (HU-09). El sistema muestra la dosis que indica el
 * esquema que definió la doctora; nunca decide la dosis por su cuenta.
 */
#[Fillable([
    'paciente_id', 'via', 'extracto', 'fecha_inicio', 'fase',
    'dosis_mantenimiento_ml', 'intervalo_mantenimiento_dias', 'estado', 'observaciones',
])]
class Inmunoterapia extends Model
{
    /** @use HasFactory<InmunoterapiaFactory> */
    use HasFactory, HasUuids, RegistraAutor;

    /**
     * Volúmenes del esquema convencional de ejemplo, por cada frasco (mL, una vez por semana).
     *
     * @var array<int, float>
     */
    public const VOLUMENES_ESQUEMA_CONVENCIONAL = [0.05, 0.10, 0.20, 0.30, 0.40, 0.50];

    protected $attributes = [
        'via' => 'subcutanea',
        'fase' => 'inicio',
        'estado' => 'activa',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'via' => ViaInmunoterapia::class,
            'fase' => FaseInmunoterapia::class,
            'estado' => EstadoInmunoterapia::class,
            'fecha_inicio' => 'date',
            'dosis_mantenimiento_ml' => 'decimal:2',
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
     * @return BelongsToMany<Alergeno, $this>
     */
    public function alergenos(): BelongsToMany
    {
        return $this->belongsToMany(Alergeno::class, 'inmunoterapia_alergeno')->withTimestamps();
    }

    /**
     * @return HasMany<EsquemaDosis, $this>
     */
    public function esquema(): HasMany
    {
        return $this->hasMany(EsquemaDosis::class)->orderBy('paso');
    }

    /**
     * @return HasMany<Frasco, $this>
     */
    public function frascos(): HasMany
    {
        return $this->hasMany(Frasco::class);
    }

    /**
     * @return HasMany<Aplicacion, $this>
     */
    public function aplicaciones(): HasMany
    {
        return $this->hasMany(Aplicacion::class);
    }

    /**
     * @return HasManyThrough<ReaccionSistemica, Aplicacion, $this>
     */
    public function reaccionesSistemicas(): HasManyThrough
    {
        return $this->hasManyThrough(ReaccionSistemica::class, Aplicacion::class);
    }

    /**
     * Fila de la vista v_proxima_aplicacion.
     *
     * @return HasOne<ProximaAplicacion, $this>
     */
    public function proximaAplicacion(): HasOne
    {
        return $this->hasOne(ProximaAplicacion::class, 'inmunoterapia_id');
    }

    public function estaActiva(): bool
    {
        return $this->estado === EstadoInmunoterapia::Activa;
    }

    public function admiteAplicaciones(): bool
    {
        return $this->estaActiva() && $this->via === ViaInmunoterapia::Subcutanea;
    }

    /**
     * Dosis que corresponde según el esquema: el paso siguiente al último aplicado
     * en la fase de inicio, o la dosis de mantenimiento. Null si no hay nada definido.
     *
     * @return array{volumen: float, dilucion: Dilucion, esquema: ?EsquemaDosis, descripcion: string}|null
     */
    public function dosisEsperada(): ?array
    {
        if ($this->fase === FaseInmunoterapia::Mantenimiento) {
            if ($this->dosis_mantenimiento_ml === null) {
                return null;
            }

            return [
                'volumen' => (float) $this->dosis_mantenimiento_ml,
                'dilucion' => Dilucion::Concentrado,
                'esquema' => null,
                'descripcion' => "Mantenimiento: {$this->dosis_mantenimiento_ml} mL del frasco ".Dilucion::Concentrado->getLabel(),
            ];
        }

        $ultimoPaso = $this->aplicaciones()
            ->whereNotNull('esquema_dosis_id')
            ->latest('aplicada_en')
            ->first()
            ?->esquemaDosis
            ?->paso ?? 0;

        $siguiente = $this->esquema()->where('paso', '>', $ultimoPaso)->first();

        if ($siguiente === null) {
            return null;
        }

        return [
            'volumen' => (float) $siguiente->volumen_ml,
            'dilucion' => $siguiente->dilucion,
            'esquema' => $siguiente,
            'descripcion' => $siguiente->descripcion(),
        ];
    }

    public function ultimaReaccionSistemica(): ?ReaccionSistemica
    {
        return $this->reaccionesSistemicas()->latest('aplicaciones.aplicada_en')->first();
    }

    public function diasDeAtraso(): ?int
    {
        return $this->proximaAplicacion?->diasDeAtraso();
    }

    public function reglaDeAtraso(): ?ReglaAtraso
    {
        $dias = $this->diasDeAtraso();

        return $dias !== null && $dias > 0 ? ReglaAtraso::paraAtraso($this->fase, $dias) : null;
    }

    /**
     * Plantilla de esquema convencional para precargar el formulario. Es un ejemplo
     * editable: la doctora define el esquema real de cada paciente.
     *
     * @param  array<int, Dilucion>  $diluciones
     * @return array<int, array{paso: int, dilucion: string, volumen_ml: float, intervalo_dias: int}>
     */
    public static function esquemaConvencional(array $diluciones = [Dilucion::Mil, Dilucion::Cien, Dilucion::Diez, Dilucion::Concentrado]): array
    {
        $pasos = [];

        foreach ($diluciones as $dilucion) {
            foreach (self::VOLUMENES_ESQUEMA_CONVENCIONAL as $volumen) {
                $pasos[] = [
                    'paso' => count($pasos) + 1,
                    'dilucion' => $dilucion->value,
                    'volumen_ml' => $volumen,
                    'intervalo_dias' => 7,
                ];
            }
        }

        return $pasos;
    }
}
