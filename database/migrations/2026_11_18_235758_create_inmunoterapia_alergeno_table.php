<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alérgenos incluidos en cada inmunoterapia (relación muchos a muchos).
 * Tiene id propio para que la auditoría pueda identificar cada fila.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmunoterapia_alergeno', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('inmunoterapia_id')->constrained('inmunoterapias')->restrictOnDelete();
            $table->foreignId('alergeno_id')->constrained('alergenos')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['inmunoterapia_id', 'alergeno_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmunoterapia_alergeno');
    }
};
