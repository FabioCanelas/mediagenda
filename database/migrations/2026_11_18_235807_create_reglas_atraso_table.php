<?php

use App\Enums\FaseInmunoterapia;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reglas que define la doctora para los atrasos (HU-13): de cuántos a cuántos
 * días de atraso y qué hacer. El sistema solo las muestra; no cambia dosis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglas_atraso', function (Blueprint $table) {
            $table->id();
            $table->string('fase', 15);
            $table->unsignedSmallInteger('dias_desde');
            $table->unsignedSmallInteger('dias_hasta')->nullable();
            $table->string('accion', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['fase', 'activo']);
        });

        Restricciones::agregar('reglas_atraso', 'chk_reglas_fase', Restricciones::enLista('fase', FaseInmunoterapia::valores()));
        Restricciones::agregar('reglas_atraso', 'chk_reglas_tramo', '`dias_desde` >= 1 AND (`dias_hasta` IS NULL OR `dias_hasta` >= `dias_desde`)');
    }

    public function down(): void
    {
        Schema::dropIfExists('reglas_atraso');
    }
};
