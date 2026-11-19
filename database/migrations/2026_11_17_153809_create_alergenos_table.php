<?php

use App\Enums\GrupoAlergeno;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de alérgenos del panel de prick test, editable por la doctora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alergenos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('nombre_cientifico', 120)->nullable();
            $table->string('grupo', 15);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Restricciones::agregar('alergenos', 'chk_alergenos_grupo', Restricciones::enLista('grupo', GrupoAlergeno::valores()));
    }

    public function down(): void
    {
        Schema::dropIfExists('alergenos');
    }
};
