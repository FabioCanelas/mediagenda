<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anexos de corrección de una nota cerrada. Solo se agregan: la aplicación
 * no tiene permiso UPDATE sobre esta tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_evolucion_anexos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('nota_evolucion_id')->constrained('notas_evolucion')->restrictOnDelete();
            $table->text('texto');
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_evolucion_anexos');
    }
};
