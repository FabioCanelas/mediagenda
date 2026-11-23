<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Prueba desde afuera el cifrado de la conexión con Supabase, el tramo que cruza
 * internet y que la base no puede ver (detrás del pooler solo ve su red interna):
 *
 *  - Supabase rechaza una conexión sin cifrar (Enforce SSL).
 *  - La aplicación acepta el servidor solo si su certificado lo firmó Supabase y
 *    corresponde al nombre al que se conecta (verify-full). Así un servidor que se
 *    haga pasar por Supabase, con otra dirección o con otro certificado, queda fuera.
 *
 * No lee ni cambia datos: cada intento solo ejecuta SELECT 1.
 */
#[Signature('base:probar-tls {--conexion=pgsql : Conexión de PostgreSQL que se prueba}')]
#[Description('Prueba que Supabase exija TLS y que la aplicación verifique el certificado del servidor')]
class ProbarTls extends Command
{
    private const CONEXION_DE_PRUEBA = 'pgsql_prueba_tls';

    public function handle(): int
    {
        $configuracion = config('database.connections.'.$this->option('conexion'));

        if (! is_array($configuracion) || ($configuracion['driver'] ?? null) !== 'pgsql') {
            $this->error('La conexión indicada no es PostgreSQL.');

            return self::FAILURE;
        }

        $host = (string) $configuracion['host'];
        $ip = gethostbyname($host);
        $autoridadFalsa = $this->crearAutoridadFalsa();

        $filas = [[
            'Modo de la conexión configurada', 'verify-full', (string) $configuracion['sslmode'],
            $configuracion['sslmode'] === 'verify-full', '',
        ]];

        $intentos = [
            'Conectar con el certificado de Supabase' => ['aceptada', []],
            'Conectar sin cifrado' => ['rechazada', ['sslmode' => 'disable']],
            'Servidor falso: otra dirección (IP en vez del nombre)' => ['rechazada', ['host' => $ip, 'sslmode' => 'verify-full']],
            'Servidor falso: certificado de otra autoridad' => ['rechazada', ['sslrootcert' => $autoridadFalsa, 'sslmode' => 'verify-full']],
        ];

        try {
            foreach ($intentos as $prueba => [$esperado, $cambios]) {
                if (in_array(null, $cambios, true) || ($cambios['host'] ?? null) === $host) {
                    $filas[] = [$prueba, $esperado, '—', false, 'No se pudo preparar la prueba en este equipo'];

                    continue;
                }

                [$obtenido, $detalle] = $this->intentar(array_merge($configuracion, $cambios));
                $filas[] = [$prueba, $esperado, $obtenido, $obtenido === $esperado, $detalle];
            }
        } finally {
            if ($autoridadFalsa !== null) {
                @unlink($autoridadFalsa);
            }
        }

        $this->table(
            ['#', 'Prueba', 'Esperado', 'Obtenido', 'OK', 'Detalle'],
            collect($filas)->map(fn (array $fila, int $indice): array => [
                $indice + 1, $fila[0], $fila[1], $fila[2], $fila[3] ? '✓' : '✗', $fila[4],
            ])->all(),
        );

        $fallas = collect($filas)->reject(fn (array $fila): bool => $fila[3])->count();

        if ($fallas > 0) {
            $this->warn("{$fallas} prueba(s) no dieron lo esperado.");

            return self::FAILURE;
        }

        $this->info('El cifrado y la verificación del servidor funcionan como se espera.');

        return self::SUCCESS;
    }

    /**
     * Abre una conexión nueva con la configuración dada y ejecuta SELECT 1.
     *
     * @param  array<string, mixed>  $configuracion
     * @return array{0: string, 1: string} Resultado (aceptada o rechazada) y el motivo del rechazo.
     */
    private function intentar(array $configuracion): array
    {
        config(['database.connections.'.self::CONEXION_DE_PRUEBA => $configuracion]);
        DB::purge(self::CONEXION_DE_PRUEBA);

        try {
            DB::connection(self::CONEXION_DE_PRUEBA)->selectOne('select 1');

            return ['aceptada', ''];
        } catch (Throwable $error) {
            $mensaje = strtok(($error->getPrevious() ?? $error)->getMessage(), "\n");
            // Se quita el prefijo "SQLSTATE... connection to server at ... failed:".
            $motivo = trim((string) preg_replace('/^.*?failed:\s*/', '', (string) $mensaje));

            return ['rechazada', mb_strimwidth($motivo, 0, 80, '…')];
        } finally {
            DB::purge(self::CONEXION_DE_PRUEBA);
        }
    }

    /**
     * Crea un certificado raíz de una autoridad inventada, como el que usaría alguien
     * que intente hacerse pasar por Supabase. Devuelve la ruta del archivo temporal.
     */
    private function crearAutoridadFalsa(): ?string
    {
        $opciones = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        // PHP para Windows no encuentra su openssl.cnf por sí solo; viene en extras/ssl.
        $archivoOpenssl = dirname(PHP_BINARY).'/extras/ssl/openssl.cnf';

        if (is_file($archivoOpenssl)) {
            $opciones['config'] = $archivoOpenssl;
        }

        $clave = openssl_pkey_new($opciones);
        $solicitud = $clave === false ? false : openssl_csr_new(['commonName' => 'Autoridad falsa de prueba'], $clave, $opciones);
        $certificado = $solicitud === false ? false : openssl_csr_sign($solicitud, null, $clave, 1, $opciones);

        if ($certificado === false || ! openssl_x509_export($certificado, $pem)) {
            return null;
        }

        $ruta = tempnam(sys_get_temp_dir(), 'ca-falsa-');

        if ($ruta === false || file_put_contents($ruta, $pem) === false) {
            return null;
        }

        return str_replace('\\', '/', $ruta);
    }
}
