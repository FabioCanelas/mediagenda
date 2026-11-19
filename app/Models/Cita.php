<?php

namespace App\Models;

use App\Enums\EstadoCita;
use App\Enums\TipoCita;
use App\Models\Concerns\RegistraAutor;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\CitaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cita de la agenda (HU-14). Las citas vigentes no se pueden cruzar:
 * lo valida la aplicación y, además, un trigger de la base.
 */
#[Fillable(['paciente_id', 'inicio', 'fin', 'tipo', 'estado', 'motivo_cancelacion', 'notas'])]
class Cita extends Model
{
    /** @use HasFactory<CitaFactory> */
    use HasFactory, HasUuids, RegistraAutor;

    /**
     * Estados que liberan el horario.
     *
     * @var array<int, string>
     */
    public const ESTADOS_QUE_LIBERAN = ['cancelada', 'no_asistio'];

    protected $attributes = [
        'estado' => 'programada',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inicio' => 'datetime',
            'fin' => 'datetime',
            'tipo' => TipoCita::class,
            'estado' => EstadoCita::class,
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
     * Citas que ocupan el horario (no canceladas ni ausentes).
     *
     * @param  Builder<Cita>  $query
     */
    #[Scope]
    protected function vigentes(Builder $query): void
    {
        $query->whereNotIn('estado', self::ESTADOS_QUE_LIBERAN);
    }

    /**
     * @param  Builder<Cita>  $query
     */
    #[Scope]
    protected function delDia(Builder $query, CarbonInterface $dia): void
    {
        $query->whereBetween('inicio', [$dia->copy()->startOfDay(), $dia->copy()->endOfDay()]);
    }

    public static function seCruza(CarbonInterface $inicio, CarbonInterface $fin, ?string $ignorarId = null): bool
    {
        return static::query()
            ->vigentes()
            ->where('inicio', '<', $fin)
            ->where('fin', '>', $inicio)
            ->when($ignorarId, fn (Builder $query) => $query->whereKeyNot($ignorarId))
            ->exists();
    }

    /**
     * Horarios libres del día para una cita de la duración indicada, dentro de la jornada
     * configurada en config/mediagenda.php.
     *
     * @return array<int, CarbonImmutable>
     */
    public static function horariosLibres(CarbonInterface $dia, int $minutos, ?string $ignorarId = null, int $maximo = 5): array
    {
        $agenda = config('mediagenda.agenda');
        $fecha = $dia->toDateString();
        $hueco = CarbonImmutable::parse("{$fecha} {$agenda['hora_inicio']}");
        $finJornada = CarbonImmutable::parse("{$fecha} {$agenda['hora_fin']}");

        $ocupadas = static::query()
            ->vigentes()
            ->delDia($dia)
            ->when($ignorarId, fn (Builder $query) => $query->whereKeyNot($ignorarId))
            ->get(['inicio', 'fin']);

        $libres = [];

        while ($hueco->addMinutes($minutos)->lte($finJornada) && count($libres) < $maximo) {
            $finHueco = $hueco->addMinutes($minutos);
            $cruza = $ocupadas->contains(fn (Cita $cita): bool => $cita->inicio->lt($finHueco) && $cita->fin->gt($hueco));

            if (! $cruza && $hueco->isFuture()) {
                $libres[] = $hueco;
            }

            $hueco = $hueco->addMinutes($agenda['intervalo_minutos']);
        }

        return $libres;
    }

    public function estaVigente(): bool
    {
        return ! in_array($this->estado->value, self::ESTADOS_QUE_LIBERAN, true);
    }
}
