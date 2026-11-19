<?php

use App\Enums\EstadoNota;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nota de evolución (NOM-004-SSA3-2012, apartado 6.2). Una vez cerrada no se
 * puede modificar ni borrar: lo impide un trigger (ver reglas_de_base_sprint_2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_evolucion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->date('fecha');
            $table->text('evolucion');
            $table->unsignedSmallInteger('presion_sistolica')->nullable();
            $table->unsignedSmallInteger('presion_diastolica')->nullable();
            $table->unsignedSmallInteger('frecuencia_cardiaca')->nullable();
            $table->unsignedSmallInteger('frecuencia_respiratoria')->nullable();
            $table->decimal('temperatura', 4, 1)->nullable();
            $table->unsignedTinyInteger('saturacion_oxigeno')->nullable();
            $table->decimal('peso_kg', 5, 2)->nullable();
            $table->text('resultados_estudios')->nullable();
            $table->text('diagnosticos')->nullable();
            $table->text('pronostico')->nullable();
            $table->text('tratamiento')->nullable();
            $table->string('estado', 10)->default(EstadoNota::Borrador->value);
            $table->timestamp('cerrada_en')->nullable();
            $table->foreignId('cerrada_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['paciente_id', 'fecha']);
        });

        Restricciones::agregar('notas_evolucion', 'chk_notas_estado', Restricciones::enLista('estado', EstadoNota::valores()));
        Restricciones::agregar('notas_evolucion', 'chk_notas_cierre', "(`estado` = 'borrador' AND `cerrada_en` IS NULL) OR (`estado` = 'cerrada' AND `cerrada_en` IS NOT NULL AND `cerrada_por` IS NOT NULL)");
        Restricciones::agregar('notas_evolucion', 'chk_notas_presion_sistolica', Restricciones::entre('presion_sistolica', 50, 260));
        Restricciones::agregar('notas_evolucion', 'chk_notas_presion_diastolica', Restricciones::entre('presion_diastolica', 30, 160));
        Restricciones::agregar('notas_evolucion', 'chk_notas_frecuencia_cardiaca', Restricciones::entre('frecuencia_cardiaca', 20, 250));
        Restricciones::agregar('notas_evolucion', 'chk_notas_frecuencia_respiratoria', Restricciones::entre('frecuencia_respiratoria', 5, 80));
        Restricciones::agregar('notas_evolucion', 'chk_notas_temperatura', Restricciones::entre('temperatura', 30, 45));
        Restricciones::agregar('notas_evolucion', 'chk_notas_saturacion', Restricciones::entre('saturacion_oxigeno', 50, 100));
        Restricciones::agregar('notas_evolucion', 'chk_notas_peso', Restricciones::entre('peso_kg', 0.5, 400));
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_evolucion');
    }
};
