<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo deja pasar si el rol del usuario tiene alguno de los permisos.
 * Uso en rutas: ->middleware('permiso:recepcion') o 'permiso:caja,cerrar_cajas'
 */
class Permiso
{
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $usuario = $request->user();
        foreach ($permisos as $permiso) {
            if ($usuario?->puede($permiso)) {
                return $next($request);
            }
        }

        abort(403, 'Tu rol no tiene permiso para entrar aquí. Pide acceso al administrador.');
    }
}
