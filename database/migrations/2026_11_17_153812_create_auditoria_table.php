<?php

use App\Support\BaseDeDatos\Auditoria;
use App\Support\BaseDeDatos\Restricciones;
use App\Support\BaseDeDatos\TriggersPostgres;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de auditoría llenada por triggers. Nadie puede modificarla ni
 * borrar sus filas: dos triggers lo impiden, incluso desde phpMyAdmin.
 */
return new class extends Migration
{
    /**
     * Tablas auditadas y columnas que nunca se copian a la bitácora.
     *
     * @var array<string, array<int, string>>
     */
    private array $tablas = [
        'users' => ['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'],
        'pacientes' => [],
        'historias_clinicas' => [],
        'diagnosticos' => [],
        'alergias' => [],
        'alergenos' => [],
        'pruebas_cutaneas' => [],
        'pruebas_cutaneas_resultados' => [],
    ];

    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->string('tabla', 64);
            $table->string('registro_id', 36);
            $table->string('accion', 10);
            $table->json('datos_antes')->nullable();
            $table->json('datos_despues')->nullable();
            // Sin llave foránea: la bitácora no depende de que el usuario siga existiendo.
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamp('realizado_en', 6)->useCurrent();

            $table->index(['tabla', 'registro_id']);
            $table->index('realizado_en');
        });

        if (Restricciones::esMysql()) {
            DB::unprepared("CREATE TRIGGER `trg_auditoria_sin_cambios` BEFORE UPDATE ON `auditoria` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La auditoria no se puede modificar'");
            DB::unprepared("CREATE TRIGGER `trg_auditoria_sin_borrado` BEFORE DELETE ON `auditoria` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La auditoria no se puede borrar'");
        }

        if (Restricciones::esPostgres()) {
            Auditoria::crearFuncionPostgres();
            TriggersPostgres::antesDe('UPDATE', 'auditoria', 'trg_auditoria_sin_cambios', TriggersPostgres::error('La auditoria no se puede modificar'));
            TriggersPostgres::antesDe('DELETE', 'auditoria', 'trg_auditoria_sin_borrado', TriggersPostgres::error('La auditoria no se puede borrar'));
        }

        foreach ($this->tablas as $tabla => $excluir) {
            Auditoria::crearTriggers($tabla, $excluir);
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->tablas) as $tabla) {
            Auditoria::eliminarTriggers($tabla);
        }

        Schema::dropIfExists('auditoria');

        if (Restricciones::esPostgres()) {
            DB::unprepared('DROP FUNCTION IF EXISTS "fn_auditoria"(), "fn_trg_auditoria_sin_cambios"(), "fn_trg_auditoria_sin_borrado"()');
        }
    }
};
