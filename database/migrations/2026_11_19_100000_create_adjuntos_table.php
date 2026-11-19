<?php

use App\Enums\TipoAdjunto;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estudios adjuntos al expediente (HU-08): laboratorios, espirometrías e imágenes.
 * El archivo vive en el disco privado; la base guarda su huella SHA-256.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjuntos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->string('tipo', 15);
            $table->date('fecha_estudio');
            $table->string('descripcion')->nullable();
            $table->string('archivo');
            $table->string('nombre_original');
            $table->string('mime', 100);
            $table->unsignedInteger('tamano_bytes');
            $table->char('sha256', 64);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['paciente_id', 'fecha_estudio']);
        });

        Restricciones::agregar('adjuntos', 'chk_adjuntos_tipo', Restricciones::enLista('tipo', TipoAdjunto::valores()));
        Restricciones::agregar('adjuntos', 'chk_adjuntos_mime', Restricciones::enLista('mime', ['application/pdf', 'image/jpeg', 'image/png']));
        Restricciones::agregar('adjuntos', 'chk_adjuntos_tamano', Restricciones::entre('tamano_bytes', 1, 10 * 1024 * 1024, admiteNulo: false));
        Restricciones::agregar('adjuntos', 'chk_adjuntos_sha256', "`sha256` REGEXP '^[0-9a-f]{64}$'");
    }

    public function down(): void
    {
        Schema::dropIfExists('adjuntos');
    }
};
