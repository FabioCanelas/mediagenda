<?php

namespace App\Models;

use App\Enums\EstadoNota;
use App\Models\Concerns\RegistraAutor;
use Database\Factories\NotaEvolucionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Nota de evolución (NOM-004, apartado 6.2). Mientras es borrador se edita;
 * al cerrarla queda fija y las correcciones van como anexos (HU-06).
 */
#[Table(name: 'notas_evolucion')]
#[Fillable([
    'paciente_id', 'fecha', 'evolucion',
    'presion_sistolica', 'presion_diastolica', 'frecuencia_cardiaca', 'frecuencia_respiratoria',
    'temperatura', 'saturacion_oxigeno', 'peso_kg',
    'resultados_estudios', 'diagnosticos', 'pronostico', 'tratamiento',
])]
class NotaEvolucion extends Model
{
    /** @use HasFactory<NotaEvolucionFactory> */
    use HasFactory, HasUuids, RegistraAutor;

    protected $attributes = [
        'estado' => 'borrador',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => EstadoNota::class,
            'temperatura' => 'decimal:1',
            'peso_kg' => 'decimal:2',
            'cerrada_en' => 'datetime',
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
     * @return HasMany<NotaEvolucionAnexo, $this>
     */
    public function anexos(): HasMany
    {
        return $this->hasMany(NotaEvolucionAnexo::class)->orderBy('created_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cerradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrada_por');
    }

    public function estaCerrada(): bool
    {
        return $this->estado === EstadoNota::Cerrada;
    }

    /**
     * Cierra la nota. Después de esto la base de datos rechaza cualquier cambio.
     */
    public function cerrar(User $doctora): void
    {
        if ($this->estaCerrada()) {
            throw new LogicException('La nota ya está cerrada.');
        }

        $this->forceFill([
            'estado' => EstadoNota::Cerrada,
            'cerrada_en' => now(),
            'cerrada_por' => $doctora->getKey(),
        ])->save();
    }
}
