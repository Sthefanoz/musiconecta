<?php

namespace App\Http\Controllers;

use App\Models\BloqueoAgenda;
use App\Models\Ciudad;
use App\Models\Genero;
use App\Models\Servicio;
use App\Models\TipoServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Búsqueda de músicos y bandas por género, ciudad, tipo de servicio, rango de precio y fecha disponible. */
class BusquedaController extends Controller
{
    public function index(Request $request): View
    {
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'genero_id' => ['nullable', 'integer'],
            'ciudad_id' => ['nullable', 'integer'],
            'tipo_servicio_id' => ['nullable', 'integer'],
            'precio_min' => ['nullable', 'numeric', 'min:0'],
            'precio_max' => ['nullable', 'numeric', 'min:0'],
            'fecha' => ['nullable', 'date'],
        ]);

        $servicios = Servicio::query()
            ->where('activo', true)
            ->with(['tipo', 'musico.ciudad', 'musico.generoPrincipal', 'banda.ciudad', 'banda.genero', 'banda.integrantes'])
            ->when($f['tipo_servicio_id'] ?? null, fn ($q, $v) => $q->where('tipo_servicio_id', $v))
            ->when($f['precio_min'] ?? null, fn ($q, $v) => $q->where('precio_base', '>=', $v))
            ->when($f['precio_max'] ?? null, fn ($q, $v) => $q->where('precio_base', '<=', $v))
            ->when($f['ciudad_id'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->whereHas('musico', fn ($m) => $m->where('ciudad_id', $v))
                ->orWhereHas('banda', fn ($b) => $b->where('ciudad_id', $v))))
            ->when($f['genero_id'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->whereHas('musico', fn ($m) => $m->where('genero_principal_id', $v)->orWhereHas('generos', fn ($g) => $g->where('generos.id', $v)))
                ->orWhereHas('banda', fn ($b) => $b->where('genero_id', $v))))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->whereHas('musico', fn ($m) => $m->where('nombre_artistico', 'like', "%{$v}%"))
                ->orWhereHas('banda', fn ($b) => $b->where('nombre', 'like', "%{$v}%"))
                ->orWhere('nombre', 'like', "%{$v}%")))
            ->orderBy('precio_base')
            ->get();

        // Fecha disponible: se descartan proveedores con algún integrante bloqueado ese día.
        if (! empty($f['fecha'])) {
            $dia = Carbon::parse($f['fecha']);
            $ocupados = BloqueoAgenda::where('inicio', '<', $dia->copy()->endOfDay())
                ->where('fin', '>', $dia->copy()->startOfDay())
                ->pluck('musico_id')->flip();
            $servicios = $servicios->filter(fn (Servicio $s) => $s->musicosParticipantes()
                ->every(fn ($m) => ! $ocupados->has($m->id)));
        }

        // Una tarjeta por proveedor (músico o banda) con sus servicios.
        $proveedores = $servicios
            ->filter(fn (Servicio $s) => ! $s->esDeBanda() || $s->banda->integrantes->isNotEmpty())
            ->groupBy(fn (Servicio $s) => $s->esDeBanda() ? 'b'.$s->banda_id : 'm'.$s->musico_id)
            ->map(function ($grupo) {
                $s = $grupo->first();
                $esBanda = $s->esDeBanda();
                $musico = $esBanda ? null : $s->musico;

                return (object) [
                    'es_banda' => $esBanda,
                    'nombre' => $s->proveedorNombre(),
                    'url' => $esBanda ? route('bandas.show', $s->banda_id) : route('musicos.show', $s->musico_id),
                    'genero' => $esBanda ? $s->banda->genero->nombre : $musico->generoPrincipal->nombre,
                    'ciudad' => $esBanda ? $s->banda->ciudad->nombre : $musico->ciudad->nombre,
                    'verificado' => $esBanda ? false : $musico->verificado,
                    'integrantes' => $esBanda ? $s->banda->integrantes->count() : 1,
                    'desde' => $grupo->min('precio_base'),
                    'servicios' => $grupo,
                ];
            })
            ->values();

        return view('busqueda.index', [
            'proveedores' => $proveedores,
            'filtros' => $f,
            'generos' => Genero::orderBy('nombre')->get(),
            'ciudades' => Ciudad::orderBy('nombre')->get(),
            'tiposServicio' => TipoServicio::orderBy('nombre')->get(),
        ]);
    }
}
