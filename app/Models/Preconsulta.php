<?php

namespace App\Models;

use App\Enums\CategoriaAlergia;
use App\Enums\EstadoAlergia;
use App\Enums\EstadoPreconsulta;
use App\Enums\RespuestaSiNo;
use App\Models\Concerns\RegistraAutor;
use Database\Factories\PreconsultaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * Pre-consulta en tablet (HU-05). La recepción genera un enlace de un solo uso;
 * el paciente responde sin iniciar sesión y sus respuestas quedan como borrador
 * hasta que la doctora las acepta o corrige y las pasa a la historia clínica.
 */
#[Fillable([
    'paciente_id', 'cita_id', 'expira_en',
    'motivo_consulta', 'tiempo_evolucion', 'sintomas', 'antecedentes_familiares',
    'alergia_medicamentos', 'alergia_medicamentos_detalle', 'otras_alergias',
    'medicamentos_actuales', 'mascotas', 'fuma', 'celular',
])]
#[Hidden(['token_hash'])]
class Preconsulta extends Model
{
    /** @use HasFactory<PreconsultaFactory> */
    use HasFactory, HasUuids, RegistraAutor;

    /**
     * @var array<string, string>
     */
    public const SINTOMAS = [
        'nariz' => 'Estornudos, nariz tapada o con moco',
        'ojos' => 'Picazón o lagrimeo de ojos',
        'pecho' => 'Tos, silbido en el pecho o falta de aire',
        'piel' => 'Ronchas, picazón o eccema en la piel',
        'alimentos' => 'Reacción después de comer algún alimento',
        'picaduras' => 'Reacción a picaduras de insectos',
        'medicamentos' => 'Reacción a algún medicamento',
    ];

    protected $attributes = [
        'estado' => 'pendiente',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoPreconsulta::class,
            'sintomas' => 'array',
            'alergia_medicamentos' => RespuestaSiNo::class,
            'fuma' => 'boolean',
            'expira_en' => 'datetime',
            'enviada_en' => 'datetime',
            'revisada_en' => 'datetime',
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
     * @return BelongsTo<Cita, $this>
     */
    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function revisadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisada_por');
    }

    /**
     * Crea el enlace para la tablet y devuelve el token en claro, que solo se
     * muestra una vez. Los enlaces anteriores sin usar del paciente se descartan.
     *
     * @return array{preconsulta: Preconsulta, token: string}
     */
    public static function preparar(Paciente $paciente, ?Cita $cita = null): array
    {
        return DB::transaction(function () use ($paciente, $cita): array {
            static::query()
                ->whereBelongsTo($paciente)
                ->where('estado', EstadoPreconsulta::Pendiente)
                ->get()
                ->each(fn (Preconsulta $anterior) => $anterior->descartar());

            $token = Str::random(48);

            $preconsulta = new static([
                'paciente_id' => $paciente->getKey(),
                'cita_id' => $cita?->getKey(),
                'expira_en' => now()->addHours(config('mediagenda.preconsulta.horas_vigencia')),
                'celular' => $paciente->celular,
            ]);
            $preconsulta->token_hash = self::hashDelToken($token);
            $preconsulta->save();

            return ['preconsulta' => $preconsulta, 'token' => $token];
        });
    }

    public static function hashDelToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Busca la pre-consulta de un enlace que todavía se puede usar.
     */
    public static function vigentePorToken(string $token): ?self
    {
        return static::query()
            ->where('token_hash', self::hashDelToken($token))
            ->where('estado', EstadoPreconsulta::Pendiente)
            ->where('expira_en', '>', now())
            ->first();
    }

    public static function enlace(string $token): string
    {
        return route('filament.admin.preconsulta', ['token' => $token]);
    }

    public function estaVigente(): bool
    {
        return $this->estado === EstadoPreconsulta::Pendiente && $this->expira_en->isFuture();
    }

    public function estaPorRevisar(): bool
    {
        return $this->estado === EstadoPreconsulta::Enviada;
    }

    /**
     * Guarda las respuestas del paciente. Después el enlace ya no sirve.
     *
     * @param  array<string, mixed>  $respuestas
     */
    public function enviar(array $respuestas): void
    {
        if (! $this->estaVigente()) {
            throw new LogicException('El enlace de la pre-consulta ya no es válido.');
        }

        $this->fill($respuestas);
        $this->estado = EstadoPreconsulta::Enviada;
        $this->enviada_en = now();
        $this->save();
    }

    /**
     * Texto propuesto para cada apartado de la historia clínica (NOM-004).
     *
     * @return array{padecimiento_actual: string, antecedentes_heredofamiliares: ?string, antecedentes_personales_patologicos: ?string, antecedentes_personales_no_patologicos: ?string}
     */
    public function propuestaParaHistoria(): array
    {
        $sintomas = collect($this->sintomas ?? [])
            ->map(fn (string $clave): string => self::SINTOMAS[$clave] ?? $clave)
            ->implode('; ');

        $patologicos = collect([
            match ($this->alergia_medicamentos) {
                RespuestaSiNo::Si => 'Refiere alergia a medicamentos: '.($this->alergia_medicamentos_detalle ?: 'sin detalle').'.',
                RespuestaSiNo::No => 'Niega alergia a medicamentos.',
                default => 'No sabe si es alérgico a medicamentos.',
            },
            filled($this->otras_alergias) ? "Otras alergias referidas: {$this->otras_alergias}." : null,
            filled($this->medicamentos_actuales) ? "Medicamentos actuales: {$this->medicamentos_actuales}." : null,
        ])->filter()->implode(' ');

        $noPatologicos = collect([
            filled($this->mascotas) ? "Mascotas: {$this->mascotas}." : 'Sin mascotas.',
            match ($this->fuma) {
                true => 'Tabaquismo positivo.',
                false => 'Niega tabaquismo.',
                default => null,
            },
        ])->filter()->implode(' ');

        return [
            'padecimiento_actual' => trim("{$this->motivo_consulta} Desde: {$this->tiempo_evolucion}.".($sintomas !== '' ? " Síntomas referidos: {$sintomas}." : '')),
            'antecedentes_heredofamiliares' => $this->antecedentes_familiares,
            'antecedentes_personales_patologicos' => $patologicos,
            'antecedentes_personales_no_patologicos' => $noPatologicos,
        ];
    }

    /**
     * La doctora acepta (o corrige) las respuestas y las pasa a la historia clínica.
     * Si un apartado ya tiene texto, el de la pre-consulta se agrega al final.
     *
     * @param  array{historia: array<string, ?string>, registrar_alergia?: bool, sustancia_alergia?: ?string, actualizar_celular?: bool}  $datos
     */
    public function pasarAHistoria(User $doctora, array $datos): HistoriaClinica
    {
        if (! $this->estaPorRevisar()) {
            throw new LogicException('Solo se revisan pre-consultas enviadas por el paciente.');
        }

        return DB::transaction(function () use ($doctora, $datos): HistoriaClinica {
            $paciente = $this->paciente;
            $historia = $paciente->historiaClinica ?? new HistoriaClinica(['paciente_id' => $paciente->getKey()]);
            $encabezado = "Pre-consulta del {$this->enviada_en->format('d/m/Y')}:";

            foreach ($datos['historia'] as $campo => $texto) {
                if (blank($texto)) {
                    continue;
                }

                $actual = $historia->getAttribute($campo);
                $historia->setAttribute($campo, blank($actual) ? $texto : "{$actual}\n\n{$encabezado} {$texto}");
            }

            $historia->save();

            if (($datos['registrar_alergia'] ?? false) && filled($datos['sustancia_alergia'] ?? null)) {
                $paciente->alergias()->create([
                    'sustancia' => $datos['sustancia_alergia'],
                    'categoria' => CategoriaAlergia::Medicamento,
                    'estado' => EstadoAlergia::Sospechada,
                    'notas' => 'Referida por el paciente en la pre-consulta.',
                ]);
            }

            if (($datos['actualizar_celular'] ?? false) && filled($this->celular)) {
                $paciente->update(['celular' => $this->celular]);
            }

            $this->forceFill([
                'estado' => EstadoPreconsulta::Revisada,
                'revisada_en' => now(),
                'revisada_por' => $doctora->getKey(),
            ])->save();

            return $historia;
        });
    }

    public function descartar(): void
    {
        $this->forceFill([
            'estado' => EstadoPreconsulta::Descartada,
            'revisada_en' => now(),
            'revisada_por' => Auth::id(),
        ])->save();
    }

    protected static function registraActualizador(): bool
    {
        return false;
    }
}
