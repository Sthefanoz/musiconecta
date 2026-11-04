<?php

namespace App\Http\Controllers;

use App\Enums\EstadoContratacion;
use App\Models\Banda;
use App\Models\Contratacion;
use App\Models\Musico;
use App\Models\Resena;
use Illuminate\View\View;

/** Perfil público con historial verificable: contrataciones realizadas, calificación y reseñas. */
class PerfilPublicoController extends Controller
{
    public function musico(Musico $musico): View
    {
        $musico->load(['ciudad', 'generoPrincipal', 'generos', 'bandas.ciudad', 'repertorio.genero', 'user']);

        $realizadas = $musico->contrataciones()->where('estado', EstadoContratacion::Realizada->value);
        $resenas = Resena::whereIn('contratacion_id', (clone $realizadas)->select('contrataciones.id'))
            ->with('contratacion.cliente', 'contratacion.servicio', 'contratacion.tipoEvento')
            ->latest()->limit(20)->get();

        return view('perfiles.musico', [
            'musico' => $musico,
            'servicios' => $musico->servicios()->where('activo', true)->with('tipo')->get(),
            'serviciosBanda' => \App\Models\Servicio::whereIn('banda_id', $musico->bandas->pluck('id'))->where('activo', true)->with('tipo', 'banda')->get(),
            'totalRealizadas' => (clone $realizadas)->count(),
            'calificacion' => $musico->calificacionPromedio(),
            'cantidadResenas' => $musico->cantidadResenas(),
            'resenas' => $resenas,
        ]);
    }

    public function banda(Banda $banda): View
    {
        $banda->load(['lider', 'ciudad', 'genero', 'integrantes', 'repertorio.genero']);

        $realizadas = Contratacion::whereIn('servicio_id', $banda->servicios()->select('id'))
            ->where('estado', EstadoContratacion::Realizada->value);
        $resenas = Resena::whereIn('contratacion_id', (clone $realizadas)->select('id'))
            ->with('contratacion.cliente', 'contratacion.servicio', 'contratacion.tipoEvento')
            ->latest()->limit(20)->get();
        $todas = Resena::whereIn('contratacion_id', (clone $realizadas)->select('id'));

        return view('perfiles.banda', [
            'banda' => $banda,
            'servicios' => $banda->servicios()->where('activo', true)->with('tipo')->get(),
            'totalRealizadas' => (clone $realizadas)->count(),
            'calificacion' => ($p = (clone $todas)->avg('puntuacion')) !== null ? round((float) $p, 1) : null,
            'cantidadResenas' => (clone $todas)->count(),
            'resenas' => $resenas,
            'porcentajesValidos' => $banda->porcentajesValidos(),
        ]);
    }
}
