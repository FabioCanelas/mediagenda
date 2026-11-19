<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fila de la bitácora de auditoría. La escriben los triggers de la base;
 * la aplicación solo la lee (el usuario de la base solo tiene SELECT).
 */
#[Table(name: 'auditoria', timestamps: false)]
class RegistroAuditoria extends Model
{
    /**
     * Campos técnicos que no aportan al leer el historial de cambios.
     *
     * @var array<int, string>
     */
    public const CAMPOS_TECNICOS = ['id', 'created_at', 'updated_at', 'created_by', 'updated_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'datos_antes' => 'array',
            'datos_despues' => 'array',
            'realizado_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Campos que cambiaron en una modificación, con su valor anterior y el nuevo.
     *
     * @return array<string, array{antes: mixed, despues: mixed}>
     */
    public function cambios(): array
    {
        $antes = $this->datos_antes ?? [];
        $despues = $this->datos_despues ?? [];
        $cambios = [];

        foreach (array_diff(array_keys($despues + $antes), self::CAMPOS_TECNICOS) as $campo) {
            if (($antes[$campo] ?? null) != ($despues[$campo] ?? null)) {
                $cambios[$campo] = ['antes' => $antes[$campo] ?? null, 'despues' => $despues[$campo] ?? null];
            }
        }

        return $cambios;
    }
}
