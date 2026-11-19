<?php

use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historia clínica con los apartados de la NOM-004-SSA3-2012 (México),
 * la norma con la que se formó la doctora. Una por paciente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historias_clinicas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paciente_id')->unique()->constrained('pacientes')->restrictOnDelete();

            // Interrogatorio
            $table->text('antecedentes_heredofamiliares')->nullable();
            $table->text('antecedentes_personales_patologicos')->nullable();
            $table->text('antecedentes_personales_no_patologicos')->nullable();
            $table->text('padecimiento_actual')->nullable();
            $table->text('interrogatorio_aparatos_sistemas')->nullable();

            // Exploración física
            $table->text('habitus_exterior')->nullable();
            $table->unsignedSmallInteger('presion_sistolica')->nullable();
            $table->unsignedSmallInteger('presion_diastolica')->nullable();
            $table->unsignedSmallInteger('frecuencia_cardiaca')->nullable();
            $table->unsignedSmallInteger('frecuencia_respiratoria')->nullable();
            $table->decimal('temperatura', 4, 1)->nullable();
            $table->unsignedTinyInteger('saturacion_oxigeno')->nullable();
            $table->decimal('peso_kg', 5, 2)->nullable();
            $table->decimal('talla_cm', 5, 1)->nullable();
            $table->text('exploracion_regional')->nullable();

            // Cierre
            $table->text('resultados_estudios')->nullable();
            $table->text('pronostico')->nullable();
            $table->text('indicacion_terapeutica')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Restricciones::agregar('historias_clinicas', 'chk_hc_presion_sistolica', Restricciones::entre('presion_sistolica', 50, 260));
        Restricciones::agregar('historias_clinicas', 'chk_hc_presion_diastolica', Restricciones::entre('presion_diastolica', 30, 160));
        Restricciones::agregar('historias_clinicas', 'chk_hc_frecuencia_cardiaca', Restricciones::entre('frecuencia_cardiaca', 20, 250));
        Restricciones::agregar('historias_clinicas', 'chk_hc_frecuencia_respiratoria', Restricciones::entre('frecuencia_respiratoria', 5, 80));
        Restricciones::agregar('historias_clinicas', 'chk_hc_temperatura', Restricciones::entre('temperatura', 30, 45));
        Restricciones::agregar('historias_clinicas', 'chk_hc_saturacion', Restricciones::entre('saturacion_oxigeno', 50, 100));
        Restricciones::agregar('historias_clinicas', 'chk_hc_peso', Restricciones::entre('peso_kg', 0.5, 400));
        Restricciones::agregar('historias_clinicas', 'chk_hc_talla', Restricciones::entre('talla_cm', 30, 250));
    }

    public function down(): void
    {
        Schema::dropIfExists('historias_clinicas');
    }
};
