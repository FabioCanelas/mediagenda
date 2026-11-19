<?php

use App\Enums\EstadoPreconsulta;
use App\Enums\RespuestaSiNo;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-consulta que el paciente llena en la tablet (HU-05). El enlace se guarda
 * como hash SHA-256: aunque alguien lea la tabla, no puede abrir el formulario.
 * Las respuestas quedan como borrador hasta que la doctora las revisa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preconsultas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignUuid('cita_id')->nullable()->constrained('citas')->restrictOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->dateTime('expira_en');
            $table->string('estado', 12)->default(EstadoPreconsulta::Pendiente->value);

            $table->text('motivo_consulta')->nullable();
            $table->string('tiempo_evolucion', 100)->nullable();
            $table->json('sintomas')->nullable();
            $table->text('antecedentes_familiares')->nullable();
            $table->string('alergia_medicamentos', 5)->nullable();
            $table->string('alergia_medicamentos_detalle')->nullable();
            $table->text('otras_alergias')->nullable();
            $table->text('medicamentos_actuales')->nullable();
            $table->string('mascotas')->nullable();
            $table->boolean('fuma')->nullable();
            $table->string('celular', 20)->nullable();

            $table->dateTime('enviada_en')->nullable();
            $table->dateTime('revisada_en')->nullable();
            $table->foreignId('revisada_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['paciente_id', 'estado']);
        });

        Restricciones::agregar('preconsultas', 'chk_preconsultas_estado', Restricciones::enLista('estado', EstadoPreconsulta::valores()));
        Restricciones::agregar('preconsultas', 'chk_preconsultas_alergia', Restricciones::enLista('alergia_medicamentos', RespuestaSiNo::valores(), admiteNulo: true));
        Restricciones::agregar(
            'preconsultas',
            'chk_preconsultas_envio',
            "`estado` = 'pendiente' OR `estado` = 'descartada'"
            .' OR (`enviada_en` IS NOT NULL AND `motivo_consulta` IS NOT NULL AND `alergia_medicamentos` IS NOT NULL)',
        );
        Restricciones::agregar(
            'preconsultas',
            'chk_preconsultas_revision',
            "`estado` NOT IN ('revisada', 'descartada') OR `revisada_en` IS NOT NULL",
        );
        Restricciones::agregar('preconsultas', 'chk_preconsultas_token', "`token_hash` REGEXP '^[0-9a-f]{64}$'");
    }

    public function down(): void
    {
        Schema::dropIfExists('preconsultas');
    }
};
