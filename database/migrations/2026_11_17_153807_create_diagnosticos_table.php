<?php

use App\Enums\TipoDiagnostico;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosticos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('historia_clinica_id')->constrained('historias_clinicas')->restrictOnDelete();
            $table->string('cie10_codigo', 10)->nullable();
            $table->foreign('cie10_codigo')->references('codigo')->on('cie10')->restrictOnDelete();
            // Texto libre cuando el diagnóstico no está en el catálogo CIE-10.
            $table->string('descripcion', 255)->nullable();
            $table->string('tipo', 15)->default(TipoDiagnostico::Principal->value);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Restricciones::agregar('diagnosticos', 'chk_diagnosticos_tipo', Restricciones::enLista('tipo', TipoDiagnostico::valores()));
        Restricciones::agregar('diagnosticos', 'chk_diagnosticos_contenido', "`cie10_codigo` IS NOT NULL OR (`descripcion` IS NOT NULL AND `descripcion` <> '')");
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosticos');
    }
};
