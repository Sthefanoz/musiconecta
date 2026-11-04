<?php

namespace App\Http\Controllers\Musico;

use App\Http\Controllers\Controller;
use App\Services\ReporteMusicoService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Panel de comparativas (Core). Por defecto: los últimos 12 meses completos. */
class PanelController extends Controller
{
    public function index(Request $request, ReporteMusicoService $reporte): View
    {
        $datos = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $hasta = isset($datos['hasta']) ? Carbon::parse($datos['hasta']) : now()->subMonthNoOverflow()->endOfMonth();
        $desde = isset($datos['desde']) ? Carbon::parse($datos['desde']) : $hasta->copy()->subMonthsNoOverflow(11)->startOfMonth();

        $musico = $request->user()->musico->load('ciudad', 'generoPrincipal');
        $r = $reporte->para($musico, $desde, $hasta);

        return view('musico.panel', [
            'musico' => $musico,
            'desde' => $desde,
            'hasta' => $hasta,
            'kpi' => $r->indicadores(),
            'mesServicio' => $r->ingresosPorMesYServicio(),
            'formas' => $r->solistaVsBanda(),
            'repertorio' => $r->repertorioSolicitado(),
            'eventos' => $r->ingresosPorTipoEvento(),
            'genero' => $r->comparacionConSuGenero(),
        ]);
    }
}
