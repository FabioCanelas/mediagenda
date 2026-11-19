<?php

use App\Enums\Brazo;
use App\Enums\FaseInmunoterapia;
use App\Enums\ReaccionLocal;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada aplicación de vacuna (HU-11). Un trigger impide usar un frasco
 * vencido, inactivo o de otro tratamiento (ver reglas_de_base_sprint_2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aplicaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('inmunoterapia_id')->constrained('inmunoterapias')->restrictOnDelete();
            $table->foreignUuid('frasco_id')->constrained('frascos')->restrictOnDelete();
            $table->foreignId('esquema_dosis_id')->nullable()->constrained('esquema_dosis')->restrictOnDelete();
            $table->string('fase', 15);
            $table->dateTime('aplicada_en');
            $table->decimal('volumen_ml', 4, 2);
            $table->boolean('dosis_difiere_esquema')->default(false);
            $table->string('brazo', 10);
            $table->string('reaccion_local', 10)->default(ReaccionLocal::Ninguna->value);
            $table->decimal('reaccion_local_mm', 4, 1)->nullable();
            $table->dateTime('observacion_hasta');
            $table->dateTime('observacion_completada_en')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('aplicada_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['inmunoterapia_id', 'aplicada_en']);
        });

        Restricciones::agregar('aplicaciones', 'chk_aplicaciones_fase', Restricciones::enLista('fase', FaseInmunoterapia::valores()));
        Restricciones::agregar('aplicaciones', 'chk_aplicaciones_volumen', Restricciones::entre('volumen_ml', 0.01, 1, admiteNulo: false));
        Restricciones::agregar('aplicaciones', 'chk_aplicaciones_brazo', Restricciones::enLista('brazo', Brazo::valores()));
        Restricciones::agregar('aplicaciones', 'chk_aplicaciones_reaccion_local', Restricciones::enLista('reaccion_local', ReaccionLocal::valores()));
        Restricciones::agregar('aplicaciones', 'chk_aplicaciones_reaccion_mm', Restricciones::entre('reaccion_local_mm', 0, 100));
        Restricciones::agregar('aplicaciones', 'chk_aplicaciones_observacion', '`observacion_hasta` >= `aplicada_en`');
    }

    public function down(): void
    {
        Schema::dropIfExists('aplicaciones');
    }
};
