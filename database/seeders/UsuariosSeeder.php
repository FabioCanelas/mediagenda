<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Usuarios de demostración. Las contraseñas se leen del archivo .env
 * (DEMO_DOCTORA_PASSWORD y DEMO_RECEPCION_PASSWORD, vía config/mediagenda.php); nunca se escriben en el código.
 * La recepcionista nace desactivada, como en el sistema real.
 */
class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        $this->crear(
            nombre: 'Dra. Demo Alergóloga',
            email: config('mediagenda.demo.doctora.email'),
            clave: config('mediagenda.demo.doctora.password'),
            rol: Rol::Doctora,
            activo: true,
        );

        $this->crear(
            nombre: 'Recepción Demo',
            email: config('mediagenda.demo.recepcion.email'),
            clave: config('mediagenda.demo.recepcion.password'),
            rol: Rol::Recepcion,
            activo: false,
        );
    }

    private function crear(string $nombre, string $email, ?string $clave, Rol $rol, bool $activo): void
    {
        if (blank($clave)) {
            throw new RuntimeException("Falta la contraseña de {$email} en el archivo .env.");
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $nombre, 'password' => $clave, 'rol' => $rol, 'activo' => $activo, 'email_verified_at' => now()],
        );
    }
}
