<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad en todas las respuestas (recomendaciones de OWASP):
 *  - El navegador no adivina tipos de archivo ni muestra el sistema dentro de otra página.
 *  - Los enlaces externos no reciben la dirección (que puede llevar el id de un paciente).
 *  - Sin cámara, micrófono ni ubicación.
 *  - Con HTTPS, el navegador ya no acepta conexiones sin cifrar (HSTS).
 *  - Las páginas con datos de pacientes no se guardan en la caché del navegador,
 *    para que nadie las vea con el botón "Atrás" en una computadora compartida.
 */
class CabecerasDeSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'");
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->remove('X-Powered-By');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (Auth::check()) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
