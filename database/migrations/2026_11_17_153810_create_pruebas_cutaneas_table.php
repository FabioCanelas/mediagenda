<?php

use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prick test: controles positivo (histamina) y negativo (solución salina),
 * y el criterio de positividad que usa la doctora (por defecto 3 mm).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pruebas_cutaneas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('control_positivo_mm', 4, 1);
            $table->decimal('control_negativo_mm', 4, 1);
            $table->decimal('criterio_positividad_mm', 3, 1)->default(3.0);
            $table->unsignedTinyInteger('minutos_lectura')->default(15);
            $table->boolean('antihistaminicos_suspendidos')->nullable();
            $table->boolean('interpretable')->default(true);
            $table->text('observaciones')->nullable();
            $table->foreignId('realizado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['paciente_id', 'fecha']);
        });

        Restricciones::agregar('pruebas_cutaneas', 'chk_pc_control_positivo', Restricciones::entre('control_positivo_mm', 0, 50, admiteNulo: false));
        Restricciones::agregar('pruebas_cutaneas', 'chk_pc_control_negativo', Restricciones::entre('control_negativo_mm', 0, 50, admiteNulo: false));
        Restricciones::agregar('pruebas_cutaneas', 'chk_pc_criterio', Restricciones::entre('criterio_positividad_mm', 1, 10, admiteNulo: false));
        Restricciones::agregar('pruebas_cutaneas', 'chk_pc_minutos', Restricciones::entre('minutos_lectura', 10, 60, admiteNulo: false));
    }

    public function down(): void
    {
        Schema::dropIfExists('pruebas_cutaneas');
    }
};
