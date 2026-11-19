<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Ejecutar con el usuario migrador, porque el catálogo CIE-10 es de solo lectura
 * para la aplicación:  php artisan db:seed --database=mysql_migrador
 *
 * No usa WithoutModelEvents: los UUID y el resultado del prick test se calculan en eventos del modelo.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            Cie10Seeder::class,
            AlergenoSeeder::class,
            UsuariosSeeder::class,
        ]);

        if (app()->isLocal()) {
            $this->call(PacientesDemoSeeder::class);
        }
    }
}
