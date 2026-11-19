<?php

use App\Enums\TipoDocumento;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Copia de cada documento entregado al paciente (HU-17 y HU-18). Es una
 * tabla de solo inserción: triggers del Sprint 3 impiden modificarla o borrarla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_emitidos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tipo', 15);
            $table->string('folio', 20)->unique();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignUuid('receta_id')->nullable()->unique()->constrained('recetas')->restrictOnDelete();
            $table->foreignUuid('consentimiento_id')->nullable()->unique()->constrained('consentimientos')->restrictOnDelete();
            $table->string('archivo');
            $table->char('sha256', 64);
            $table->unsignedInteger('tamano_bytes');
            $table->foreignId('emitido_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('emitido_en')->useCurrent();

            $table->index(['paciente_id', 'emitido_en']);
        });

        Restricciones::agregar('documentos_emitidos', 'chk_documentos_tipo', Restricciones::enLista('tipo', TipoDocumento::valores()));
        Restricciones::agregar(
            'documentos_emitidos',
            'chk_documentos_origen',
            "(`tipo` = 'receta' AND `receta_id` IS NOT NULL AND `consentimiento_id` IS NULL)"
            ." OR (`tipo` = 'consentimiento' AND `consentimiento_id` IS NOT NULL AND `receta_id` IS NULL)",
        );
        Restricciones::agregar('documentos_emitidos', 'chk_documentos_sha256', "`sha256` REGEXP '^[0-9a-f]{64}$'");
        Restricciones::agregar('documentos_emitidos', 'chk_documentos_folio', "`folio` REGEXP '^[RC]-[0-9]{4}-[0-9]{6}$'");
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_emitidos');
    }
};
