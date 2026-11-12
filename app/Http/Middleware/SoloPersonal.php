<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** El panel es solo para el personal (usuarios con rol). Un cliente vuelve a la web. */
class SoloPersonal
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->esPersonal()) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
