<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoContratacion;
use App\Http\Controllers\Controller;
use App\Models\Contratacion;
use App\Models\Musico;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Comisión ganada por la plataforma por mes y músicos con más contrataciones. */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $anio = (int) $request->query('anio', now()->year);
        $realizadas = Contratacion::where('estado', EstadoContratacion::Realizada->value);

        $comisionMes = (clone $realizadas)
            ->whereYear('fecha_evento', $anio)
            ->selectRaw("substr(fecha_evento, 1, 7) as mes, sum(comision) as comision, sum(monto_total) as facturado, count(*) as cantidad")
            ->groupBy('mes')->orderBy('mes')
            ->get()->keyBy('mes');

        $meses = collect(range(1, 12))->map(fn ($m) => sprintf('%d-%02d', $anio, $m));

        $topMusicos = DB::table('repartos as r')
            ->join('contrataciones as c', 'c.id', '=', 'r.contratacion_id')
            ->join('musicos as m', 'm.id', '=', 'r.musico_id')
            ->where('c.estado', EstadoContratacion::Realizada->value)
            ->whereYear('c.fecha_evento', $anio)
            ->groupBy('m.id', 'm.nombre_artistico', 'm.verificado')
            ->selectRaw('m.id, m.nombre_artistico, m.verificado, count(*) as contrataciones, sum(r.monto_neto) as ingresos')
            ->orderByDesc('contrataciones')
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'anio' => $anio,
            'meses' => $meses,
            'comisionMes' => $comisionMes,
            'totalComision' => $comisionMes->sum('comision'),
            'totalFacturado' => $comisionMes->sum('facturado'),
            'topMusicos' => $topMusicos,
            'stats' => [
                'musicos' => Musico::count(),
                'verificados' => Musico::where('verificado', true)->count(),
                'clientes' => User::where('rol', 'cliente')->count(),
                'pendientes' => Contratacion::where('estado', EstadoContratacion::Solicitada->value)->count(),
            ],
        ]);
    }
}
