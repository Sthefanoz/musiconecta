<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Un usuario con rol músico debe completar su perfil antes de usar el resto de funciones. */
class PerfilMusicoCompleto
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->esMusico() && ! $request->user()->musico) {
            return redirect()->route('musico.perfil.edit')
                ->with('error', 'Completa tu perfil de músico para continuar.');
        }

        return $next($request);
    }
}
