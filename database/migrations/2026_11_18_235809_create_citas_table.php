<?php

use App\Enums\EstadoCita;
use App\Enums\TipoCita;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agenda (HU-14). Un trigger impide que dos citas vigentes se crucen.
 * Las citas no se borran: se cancelan con un motivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->dateTime('inicio');
            $table->dateTime('fin');
            $table->string('tipo', 20);
            $table->string('estado', 12)->default(EstadoCita::Programada->value);
            $table->string('motivo_cancelacion', 255)->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['inicio', 'estado']);
            $table->index(['paciente_id', 'inicio']);
        });

        Restricciones::agregar('citas', 'chk_citas_tipo', Restricciones::enLista('tipo', TipoCita::valores()));
        Restricciones::agregar('citas', 'chk_citas_estado', Restricciones::enLista('estado', EstadoCita::valores()));
        Restricciones::agregar('citas', 'chk_citas_horario', '`fin` > `inicio`');
        Restricciones::agregar('citas', 'chk_citas_cancelacion', "`estado` <> 'cancelada' OR `motivo_cancelacion` IS NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
