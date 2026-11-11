<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ciudad;
use App\Models\Provincia;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Ciudades: cada una pertenece a una provincia (se elige con un dropdown, nunca escribiendo el id). */
class CiudadController extends Controller
{
    public function index(Request $request): View
    {
        // select() va antes de withCount(): si va después, borra la columna musicos_count.
        $ciudades = Ciudad::query()
            ->select('ciudades.*')
            ->with('provincia')
            ->withCount('musicos')
            ->when($request->query('provincia_id'), fn ($q, $v) => $q->where('ciudades.provincia_id', $v))
            ->join('provincias', 'provincias.id', '=', 'ciudades.provincia_id')
            ->orderBy('provincias.nombre')->orderBy('ciudades.nombre')
            ->get();

        return view('admin.ciudades', [
            'ciudades' => $ciudades,
            'provincias' => Provincia::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Ciudad::create($this->validar($request));

        return back()->with('ok', 'Ciudad creada.');
    }

    public function update(Request $request, Ciudad $ciudad): RedirectResponse
    {
        $ciudad->update($this->validar($request, $ciudad));

        return back()->with('ok', 'Ciudad actualizada.');
    }

    public function destroy(Ciudad $ciudad): RedirectResponse
    {
        try {
            $ciudad->delete();
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar «'.$ciudad->nombre.'»: hay músicos, bandas o contrataciones que la usan.');
        }

        return back()->with('ok', 'Ciudad eliminada.');
    }

    private function validar(Request $request, ?Ciudad $ciudad = null): array
    {
        return $request->validate([
            'provincia_id' => ['required', 'exists:provincias,id'],
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('ciudades', 'nombre')->where('provincia_id', $request->input('provincia_id'))->ignore($ciudad?->id),
            ],
        ], ['nombre.unique' => 'Esa provincia ya tiene una ciudad con ese nombre.']);
    }
}
