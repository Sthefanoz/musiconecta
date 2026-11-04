<?php

namespace App\Http\Controllers\Musico;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use App\Models\TipoServicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Servicios del músico: como solista o con una banda que lidera. */
class ServicioController extends Controller
{
    public function index(Request $request): View
    {
        $musico = $request->user()->musico;
        $bandaIds = $musico->bandasLideradas()->pluck('id');

        $servicios = Servicio::where('musico_id', $musico->id)
            ->orWhereIn('banda_id', $bandaIds)
            ->with(['tipo', 'banda'])
            ->withCount('contrataciones')
            ->orderByDesc('activo')->orderBy('nombre')
            ->get();

        return view('musico.servicios.index', compact('servicios'));
    }

    public function create(Request $request): View
    {
        return $this->formulario($request, new Servicio(['activo' => true, 'duracion_horas' => 1]));
    }

    public function store(Request $request): RedirectResponse
    {
        Servicio::create($this->validar($request));

        return redirect()->route('musico.servicios.index')->with('ok', 'Servicio publicado.');
    }

    public function edit(Request $request, Servicio $servicio): View
    {
        $this->autorizar($request, $servicio);

        return $this->formulario($request, $servicio);
    }

    public function update(Request $request, Servicio $servicio): RedirectResponse
    {
        $this->autorizar($request, $servicio);
        $servicio->update($this->validar($request));

        return redirect()->route('musico.servicios.index')->with('ok', 'Servicio actualizado.');
    }

    /** Desactivar / reactivar (no se eliminan para conservar el historial). */
    public function toggle(Request $request, Servicio $servicio): RedirectResponse
    {
        $this->autorizar($request, $servicio);
        $servicio->update(['activo' => ! $servicio->activo]);

        return back()->with('ok', $servicio->activo ? 'Servicio reactivado.' : 'Servicio desactivado.');
    }

    private function formulario(Request $request, Servicio $servicio): View
    {
        return view('musico.servicios.form', [
            'servicio' => $servicio,
            'tipos' => TipoServicio::orderBy('nombre')->get(),
            'bandas' => $request->user()->musico->bandasLideradas()->orderBy('nombre')->get(),
        ]);
    }

    private function validar(Request $request): array
    {
        $musico = $request->user()->musico;
        $datos = $request->validate([
            'tipo_servicio_id' => ['required', 'exists:tipos_servicio,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'precio_base' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'duracion_horas' => ['required', 'numeric', 'min:0.5', 'max:24'],
            // "solista" o el id de una banda que lidera
            'ofrecido_como' => ['required', Rule::in(['solista', ...$musico->bandasLideradas()->pluck('id')->map(fn ($id) => (string) $id)])],
        ]);

        $esSolista = $datos['ofrecido_como'] === 'solista';
        $datos['musico_id'] = $esSolista ? $musico->id : null;
        $datos['banda_id'] = $esSolista ? null : (int) $datos['ofrecido_como'];
        unset($datos['ofrecido_como']);

        return $datos;
    }

    private function autorizar(Request $request, Servicio $servicio): void
    {
        $musico = $request->user()->musico;
        abort_unless(
            $servicio->musico_id === $musico->id || ($servicio->banda && $servicio->banda->lider_id === $musico->id),
            403
        );
    }
}
