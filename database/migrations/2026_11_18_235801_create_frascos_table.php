<?php

use App\Enums\Dilucion;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frascos de extracto de cada inmunoterapia (HU-10). El color siempre
 * corresponde a la dilución: lo exige una restricción CHECK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frascos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('inmunoterapia_id')->constrained('inmunoterapias')->restrictOnDelete();
            $table->string('dilucion', 8);
            $table->string('color', 10);
            $table->string('lote', 40);
            $table->date('fecha_preparacion')->nullable();
            $table->date('fecha_vencimiento');
            $table->boolean('activo')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['inmunoterapia_id', 'activo']);
        });

        $colores = collect(Dilucion::cases())
            ->map(fn (Dilucion $dilucion): string => "(`dilucion` = '{$dilucion->value}' AND `color` = '{$dilucion->color()->value}')")
            ->implode(' OR ');

        Restricciones::agregar('frascos', 'chk_frascos_dilucion', Restricciones::enLista('dilucion', Dilucion::valores()));
        Restricciones::agregar('frascos', 'chk_frascos_color_segun_dilucion', $colores);
        Restricciones::agregar('frascos', 'chk_frascos_fechas', '`fecha_preparacion` IS NULL OR `fecha_vencimiento` >= `fecha_preparacion`');
    }

    public function down(): void
    {
        Schema::dropIfExists('frascos');
    }
};
