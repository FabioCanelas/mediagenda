<?php

use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuestionario de control del asma (HU-16). El puntaje es una columna
 * generada por la base: siempre es la suma de las 5 respuestas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuestionarios_act', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->date('fecha');

            foreach (range(1, 5) as $pregunta) {
                $table->unsignedTinyInteger("respuesta_{$pregunta}");
            }

            $table->unsignedTinyInteger('puntaje')->storedAs('respuesta_1 + respuesta_2 + respuesta_3 + respuesta_4 + respuesta_5');
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['paciente_id', 'fecha']);
        });

        foreach (range(1, 5) as $pregunta) {
            Restricciones::agregar('cuestionarios_act', "chk_act_respuesta_{$pregunta}", Restricciones::entre("respuesta_{$pregunta}", 1, 5, admiteNulo: false));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cuestionarios_act');
    }
};
