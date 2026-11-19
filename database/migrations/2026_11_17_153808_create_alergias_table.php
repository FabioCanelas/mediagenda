<?php

use App\Enums\CategoriaAlergia;
use App\Enums\CriticidadAlergia;
use App\Enums\EstadoAlergia;
use App\Enums\GravedadReaccion;
use App\Enums\TipoAlergia;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alergias del paciente, con los campos del recurso AllergyIntolerance de
 * HL7 FHIR (categoría, criticidad, estado de verificación y reacción).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alergias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->string('sustancia', 120);
            $table->string('categoria', 15);
            $table->string('tipo', 15)->default(TipoAlergia::Alergia->value);
            $table->string('criticidad', 15)->default(CriticidadAlergia::NoEvaluable->value);
            $table->string('estado', 15)->default(EstadoAlergia::Sospechada->value);
            $table->text('reaccion')->nullable();
            $table->string('gravedad', 10)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['paciente_id', 'estado']);
        });

        Restricciones::agregar('alergias', 'chk_alergias_categoria', Restricciones::enLista('categoria', CategoriaAlergia::valores()));
        Restricciones::agregar('alergias', 'chk_alergias_tipo', Restricciones::enLista('tipo', TipoAlergia::valores()));
        Restricciones::agregar('alergias', 'chk_alergias_criticidad', Restricciones::enLista('criticidad', CriticidadAlergia::valores()));
        Restricciones::agregar('alergias', 'chk_alergias_estado', Restricciones::enLista('estado', EstadoAlergia::valores()));
        Restricciones::agregar('alergias', 'chk_alergias_gravedad', Restricciones::enLista('gravedad', GravedadReaccion::valores(), admiteNulo: true));
    }

    public function down(): void
    {
        Schema::dropIfExists('alergias');
    }
};
