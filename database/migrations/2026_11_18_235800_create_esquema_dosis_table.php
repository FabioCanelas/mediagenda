<?php

use App\Enums\Dilucion;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pasos de la fase de inicio: qué frasco (dilución), cuánto volumen y cada
 * cuántos días. Un paso ya aplicado no se puede borrar (llave foránea RESTRICT
 * desde aplicaciones).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esquema_dosis', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('inmunoterapia_id')->constrained('inmunoterapias')->restrictOnDelete();
            $table->unsignedSmallInteger('paso');
            $table->string('dilucion', 8);
            $table->decimal('volumen_ml', 4, 2);
            $table->unsignedSmallInteger('intervalo_dias')->default(7);
            $table->timestamps();

            $table->unique(['inmunoterapia_id', 'paso']);
        });

        Restricciones::agregar('esquema_dosis', 'chk_esquema_dilucion', Restricciones::enLista('dilucion', Dilucion::valores()));
        Restricciones::agregar('esquema_dosis', 'chk_esquema_volumen', Restricciones::entre('volumen_ml', 0.01, 1, admiteNulo: false));
        Restricciones::agregar('esquema_dosis', 'chk_esquema_intervalo', Restricciones::entre('intervalo_dias', 1, 60, admiteNulo: false));
        Restricciones::agregar('esquema_dosis', 'chk_esquema_paso', '`paso` >= 1');
    }

    public function down(): void
    {
        Schema::dropIfExists('esquema_dosis');
    }
};
