<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Prepara Supabase (PostgreSQL) antes de la primera migración. Se ejecuta una sola
 * vez desde la computadora local, con el usuario administrador de Supabase:
 *
 *  - Crea los roles mediagenda_migrador (dueño de las tablas), mediagenda_app
 *    (la aplicación) y mediagenda_auditor (sin login, para pgaudit).
 *  - Crea el esquema mediagenda, que la API automática de Supabase no publica,
 *    y les quita todo acceso a los roles públicos de Supabase (anon, authenticated).
 *  - Limita al usuario de la aplicación: conexiones, tiempo por consulta y search_path.
 *  - Activa pgaudit para registrar las lecturas de las tablas clínicas.
 *
 * Las contraseñas se leen del .env y nunca se muestran. Se puede repetir sin riesgo.
 */
#[Signature('base:preparar-postgres {--conexion=pgsql_admin : Conexión con el usuario administrador de Supabase}')]
#[Description('Crea en Supabase los roles, el esquema y los límites de seguridad de MediAgenda')]
class PrepararPostgres extends Command
{
    private int $fallas = 0;

    public function handle(): int
    {
        $db = DB::connection($this->option('conexion'));

        if ($db->getDriverName() !== 'pgsql') {
            $this->error('La conexión indicada no es PostgreSQL.');

            return self::FAILURE;
        }

        $esquema = (string) config('database.connections.pgsql.search_path');
        $app = $this->rol('database.connections.pgsql.username');
        $migrador = $this->rol('database.connections.pgsql_migrador.username');
        $auditor = 'mediagenda_auditor';
        $claveApp = (string) config('database.connections.pgsql.password');
        $claveMigrador = (string) config('database.connections.pgsql_migrador.password');

        if ($app === '' || $migrador === '' || strlen($claveApp) < 16 || strlen($claveMigrador) < 16) {
            $this->error('Faltan PG_USERNAME, PG_MIGRATION_USERNAME o sus contraseñas (mínimo 16 caracteres) en el .env.');

            return self::FAILURE;
        }

        $this->info('Roles');
        foreach ([$migrador => 'LOGIN', $app => 'LOGIN', $auditor => 'NOLOGIN'] as $rol => $login) {
            $this->paso("Crear {$rol}", "DO \$\$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = '{$rol}') THEN CREATE ROLE \"{$rol}\" {$login}; END IF; END \$\$", $db);
        }

        $this->paso("Contraseña y límites de {$migrador}", "ALTER ROLE \"{$migrador}\" WITH LOGIN NOCREATEDB NOCREATEROLE NOINHERIT CONNECTION LIMIT 3 PASSWORD {$this->literal($db, $claveMigrador)}", $db);
        $this->paso("Contraseña y límites de {$app}", "ALTER ROLE \"{$app}\" WITH LOGIN NOCREATEDB NOCREATEROLE NOINHERIT CONNECTION LIMIT 10 PASSWORD {$this->literal($db, $claveApp)}", $db);
        $this->paso('El administrador puede actuar como estos roles', "GRANT \"{$migrador}\", \"{$app}\" TO CURRENT_USER", $db);

        $this->info('Esquema');
        $this->paso("Crear el esquema {$esquema}", "CREATE SCHEMA IF NOT EXISTS \"{$esquema}\" AUTHORIZATION \"{$migrador}\"", $db);
        $this->paso('Nadie más entra al esquema', "REVOKE ALL ON SCHEMA \"{$esquema}\" FROM PUBLIC", $db);
        $this->paso('La aplicación solo puede usarlo (no crear tablas)', "GRANT USAGE ON SCHEMA \"{$esquema}\" TO \"{$app}\"", $db);

        foreach (['anon', 'authenticated'] as $rolPublico) {
            $this->paso("Sin acceso para {$rolPublico} (API pública de Supabase)", "DO \$\$ BEGIN IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = '{$rolPublico}') THEN REVOKE ALL ON SCHEMA \"{$esquema}\" FROM \"{$rolPublico}\"; END IF; END \$\$", $db);
        }

        $this->info('Límites de sesión');
        foreach ([$app, $migrador] as $rol) {
            $this->paso("search_path de {$rol}", "ALTER ROLE \"{$rol}\" SET search_path = \"{$esquema}\"", $db);
        }
        $this->paso('Una consulta de la aplicación no dura más de 15 s', "ALTER ROLE \"{$app}\" SET statement_timeout = '15s'", $db);
        $this->paso('Una transacción abierta sin uso se corta a los 60 s', "ALTER ROLE \"{$app}\" SET idle_in_transaction_session_timeout = '60s'", $db);

        $this->info('pgaudit (registro de lecturas)');
        $this->paso('Extensión pgaudit', 'CREATE EXTENSION IF NOT EXISTS pgaudit', $db, opcional: true);
        $this->paso("Auditar lo que {$app} lee de las tablas clínicas", "ALTER ROLE \"{$app}\" SET pgaudit.role = '{$auditor}'", $db, opcional: true);
        $this->paso('Registrar los cambios de estructura', "ALTER ROLE \"{$migrador}\" SET pgaudit.log = 'ddl, role'", $db, opcional: true);

        $this->newLine();

        if ($this->fallas > 0) {
            $this->warn("Terminó con {$this->fallas} paso(s) sin aplicar. Revisa los mensajes de arriba.");

            return self::FAILURE;
        }

        $this->info('Listo. Sigue con: php artisan migrate --database=pgsql_migrador');

        return self::SUCCESS;
    }

    /**
     * @param  bool  $opcional  Si falla, se avisa pero no se cuenta como error (depende del plan de Supabase).
     */
    private function paso(string $descripcion, string $sql, Connection $db, bool $opcional = false): void
    {
        try {
            $db->unprepared($sql);
            $this->line("  <fg=green>✓</> {$descripcion}");
        } catch (Throwable $error) {
            $this->fallas += $opcional ? 0 : 1;
            // El mensaje del driver no incluye la sentencia (la de QueryException sí).
            $mensaje = strtok(($error->getPrevious() ?? $error)->getMessage(), "\n");
            $marca = $opcional ? '<fg=yellow>⚠</>' : '<fg=red>✗</>';
            // Nunca se muestra la sentencia: puede llevar una contraseña.
            $this->line("  {$marca} {$descripcion}: ".preg_replace("/PASSWORD '[^']*'/", "PASSWORD '***'", (string) $mensaje));
        }
    }

    /**
     * El pooler de Supabase recibe el usuario como rol.proyecto; el rol es la primera parte.
     */
    private function rol(string $clave): string
    {
        return explode('.', (string) config($clave))[0];
    }

    private function literal(Connection $db, string $valor): string
    {
        return $db->getPdo()->quote($valor);
    }
}
