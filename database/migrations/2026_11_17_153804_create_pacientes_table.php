<?php

use App\Enums\Departamento;
use App\Enums\Sexo;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ci', 15);
            // Cadena vacía en lugar de NULL para que el índice único no admita CI repetidos.
            $table->string('ci_complemento', 5)->default('');
            $table->string('ci_expedido', 2)->nullable();
            $table->string('nombres', 80);
            $table->string('apellido_paterno', 60)->nullable();
            $table->string('apellido_materno', 60)->nullable();
            $table->date('fecha_nacimiento');
            $table->char('sexo', 1);
            $table->string('celular', 20)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('ocupacion', 80)->nullable();
            $table->string('contacto_emergencia_nombre', 120)->nullable();
            $table->string('contacto_emergencia_telefono', 20)->nullable();
            $table->string('tutor_nombre', 120)->nullable();
            $table->string('tutor_ci', 20)->nullable();
            $table->string('tutor_parentesco', 40)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['ci', 'ci_complemento']);
            $table->index(['apellido_paterno', 'apellido_materno', 'nombres']);
        });

        Restricciones::agregar('pacientes', 'chk_pacientes_ci', "`ci` REGEXP '^[0-9]{5,12}$'");
        Restricciones::agregar('pacientes', 'chk_pacientes_ci_expedido', Restricciones::enLista('ci_expedido', Departamento::valores(), admiteNulo: true));
        Restricciones::agregar('pacientes', 'chk_pacientes_sexo', Restricciones::enLista('sexo', Sexo::valores()));
        Restricciones::agregar('pacientes', 'chk_pacientes_apellido', '`apellido_paterno` IS NOT NULL OR `apellido_materno` IS NOT NULL');
        Restricciones::agregar('pacientes', 'chk_pacientes_fecha_nacimiento', "`fecha_nacimiento` >= '1900-01-01'");
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
