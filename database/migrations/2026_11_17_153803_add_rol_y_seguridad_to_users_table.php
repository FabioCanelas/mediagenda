<?php

use App\Enums\Rol;
use App\Support\BaseDeDatos\Restricciones;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Un usuario nuevo nace con el rol de menor privilegio y desactivado.
            $table->string('rol', 20)->default(Rol::Recepcion->value)->after('email');
            $table->boolean('activo')->default(false)->after('rol');
            // Verificación en dos pasos (Filament). La aplicación guarda ambos valores cifrados.
            $table->text('app_authentication_secret')->nullable()->after('password');
            $table->text('app_authentication_recovery_codes')->nullable()->after('app_authentication_secret');
        });

        Restricciones::agregar('users', 'chk_users_rol', Restricciones::enLista('rol', Rol::valores()));
    }

    public function down(): void
    {
        Restricciones::quitar('users', 'chk_users_rol');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rol', 'activo', 'app_authentication_secret', 'app_authentication_recovery_codes']);
        });
    }
};
