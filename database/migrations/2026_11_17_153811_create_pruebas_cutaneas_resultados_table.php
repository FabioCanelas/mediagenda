<?php

use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pruebas_cutaneas_resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('prueba_cutanea_id')->constrained('pruebas_cutaneas')->restrictOnDelete();
            $table->foreignId('alergeno_id')->constrained('alergenos')->restrictOnDelete();
            $table->decimal('papula_mm', 4, 1);
            $table->decimal('eritema_mm', 4, 1)->nullable();
            $table->boolean('positivo')->default(false);
            $table->timestamps();

            $table->unique(['prueba_cutanea_id', 'alergeno_id']);
        });

        Restricciones::agregar('pruebas_cutaneas_resultados', 'chk_pcr_papula', Restricciones::entre('papula_mm', 0, 50, admiteNulo: false));
        Restricciones::agregar('pruebas_cutaneas_resultados', 'chk_pcr_eritema', Restricciones::entre('eritema_mm', 0, 100));
    }

    public function down(): void
    {
        Schema::dropIfExists('pruebas_cutaneas_resultados');
    }
};
