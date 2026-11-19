<?php

use App\Enums\EstadoInmunoterapia;
use App\Enums\FaseInmunoterapia;
use App\Enums\ViaInmunoterapia;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Receta de inmunoterapia (HU-09). El esquema de inicio está en esquema_dosis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmunoterapias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->string('via', 12)->default(ViaInmunoterapia::Subcutanea->value);
            $table->string('extracto', 150);
            $table->date('fecha_inicio');
            $table->string('fase', 15)->default(FaseInmunoterapia::Inicio->value);
            $table->decimal('dosis_mantenimiento_ml', 4, 2)->nullable();
            $table->unsignedSmallInteger('intervalo_mantenimiento_dias')->nullable();
            $table->string('estado', 12)->default(EstadoInmunoterapia::Activa->value);
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['paciente_id', 'estado']);
        });

        Restricciones::agregar('inmunoterapias', 'chk_inmuno_via', Restricciones::enLista('via', ViaInmunoterapia::valores()));
        Restricciones::agregar('inmunoterapias', 'chk_inmuno_fase', Restricciones::enLista('fase', FaseInmunoterapia::valores()));
        Restricciones::agregar('inmunoterapias', 'chk_inmuno_estado', Restricciones::enLista('estado', EstadoInmunoterapia::valores()));
        Restricciones::agregar('inmunoterapias', 'chk_inmuno_dosis_mantenimiento', Restricciones::entre('dosis_mantenimiento_ml', 0.01, 1));
        Restricciones::agregar('inmunoterapias', 'chk_inmuno_intervalo_mantenimiento', Restricciones::entre('intervalo_mantenimiento_dias', 7, 90));
    }

    public function down(): void
    {
        Schema::dropIfExists('inmunoterapias');
    }
};
