<?php

use App\Enums\EstadoConsentimiento;
use App\Enums\ProcedimientoConsentimiento;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consentimiento informado (HU-18). Una vez emitido solo cambia su firma:
 * lo controla un trigger de la migración de reglas del Sprint 3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimientos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->string('procedimiento', 15);
            $table->date('fecha');
            $table->string('lugar', 100);
            $table->string('firmante_nombre', 150);
            $table->boolean('firmante_es_tutor')->default(false);
            $table->string('estado', 10)->default(EstadoConsentimiento::Pendiente->value);
            $table->timestamp('firmado_en')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['paciente_id', 'procedimiento', 'estado']);
        });

        Restricciones::agregar('consentimientos', 'chk_consentimientos_procedimiento', Restricciones::enLista('procedimiento', ProcedimientoConsentimiento::valores()));
        Restricciones::agregar('consentimientos', 'chk_consentimientos_estado', Restricciones::enLista('estado', EstadoConsentimiento::valores()));
        Restricciones::agregar(
            'consentimientos',
            'chk_consentimientos_firma',
            "(`estado` = 'pendiente' AND `firmado_en` IS NULL) OR (`estado` = 'firmado' AND `firmado_en` IS NOT NULL)",
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimientos');
    }
};
