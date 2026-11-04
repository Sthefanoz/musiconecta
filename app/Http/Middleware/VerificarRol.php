<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Uso: ->middleware('rol:musico') o 'rol:admin,cliente' */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && $user->tieneRol(...$roles), 403, 'No tienes permiso para acceder a esta sección.');

        return $next($request);
    }
}
