<?php

use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recetas e indicaciones (HU-17). Una receta tiene uno o más medicamentos;
 * cuando se emite el PDF ya no se modifica (trigger del Sprint 3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recetas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->date('fecha');
            $table->text('indicaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['paciente_id', 'fecha']);
        });

        Schema::create('receta_medicamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('receta_id')->constrained('recetas')->restrictOnDelete();
            $table->unsignedSmallInteger('orden');
            $table->string('medicamento', 150);
            $table->string('presentacion', 100)->nullable();
            $table->string('dosis', 150);
            $table->string('duracion', 100)->nullable();
            $table->string('indicaciones')->nullable();
            $table->timestamps();

            $table->unique(['receta_id', 'orden']);
        });

        Restricciones::agregar('receta_medicamentos', 'chk_receta_medicamentos_orden', '`orden` >= 1');
    }

    public function down(): void
    {
        Schema::dropIfExists('receta_medicamentos');
        Schema::dropIfExists('recetas');
    }
};
