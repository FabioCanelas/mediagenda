<?php

namespace App\Http\Middleware;

use App\Support\BaseDeDatos\Restricciones;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le dice a la base de datos qué usuario está trabajando, para que los
 * triggers de auditoría lo registren en cada cambio (@app_usuario_id).
 */
class EstablecerUsuarioAuditoria
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Restricciones::soportado()) {
            DB::statement('SET @app_usuario_id = ?', [Auth::id()]);
        }

        return $next($request);
    }
}
