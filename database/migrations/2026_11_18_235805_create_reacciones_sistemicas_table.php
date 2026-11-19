<?php

use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reacción sistémica después de una aplicación, con el grado de la escala WAO (HU-12).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reacciones_sistemicas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('aplicacion_id')->unique()->constrained('aplicaciones')->restrictOnDelete();
            $table->unsignedTinyInteger('grado');
            $table->unsignedSmallInteger('minutos_desde_aplicacion')->nullable();
            $table->text('sintomas');
            $table->text('tratamiento')->nullable();
            $table->boolean('epinefrina')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Restricciones::agregar('reacciones_sistemicas', 'chk_reacciones_grado', Restricciones::entre('grado', 1, 5, admiteNulo: false));
        Restricciones::agregar('reacciones_sistemicas', 'chk_reacciones_minutos', Restricciones::entre('minutos_desde_aplicacion', 0, 1440));
    }

    public function down(): void
    {
        Schema::dropIfExists('reacciones_sistemicas');
    }
};
