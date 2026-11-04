<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cancion;
use App\Models\Genero;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Catálogo de canciones. Alimenta la comparativa "Repertorio solicitado" del Core:
 * el estilo de cada canción define cómo se reparte el ingreso entre estilos.
 */
class CancionController extends Controller
{
    public function index(Request $request): View
    {
        $canciones = Cancion::with('genero')
            ->addSelect(['pedidos' => DB::table('contratacion_cancion')->selectRaw('count(*)')->whereColumn('cancion_id', 'canciones.id')])
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('titulo', 'like', "%{$v}%")->orWhere('artista_original', 'like', "%{$v}%")))
            ->when($request->query('genero_id'), fn ($q, $v) => $q->where('genero_id', $v))
            ->orderBy('titulo')
            ->paginate(30)->withQueryString();

        return view('admin.canciones', [
            'canciones' => $canciones,
            'generos' => Genero::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Cancion::create($this->validar($request));

        return back()->with('ok', 'Canción agregada al catálogo.');
    }

    public function update(Request $request, Cancion $cancion): RedirectResponse
    {
        $cancion->update($this->validar($request, $cancion));

        return back()->with('ok', 'Canción actualizada.');
    }

    public function destroy(Cancion $cancion): RedirectResponse
    {
        if (DB::table('contratacion_cancion')->where('cancion_id', $cancion->id)->exists()) {
            return back()->with('error', 'No se puede eliminar «'.$cancion->titulo.'»: ya fue solicitada en contrataciones (forma parte del historial).');
        }

        try {
            $cancion->delete();
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar la canción.');
        }

        return back()->with('ok', 'Canción eliminada.');
    }

    private function validar(Request $request, ?Cancion $cancion = null): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'artista_original' => [
                'nullable', 'string', 'max:150',
                Rule::unique('canciones', 'artista_original')->where('titulo', $request->input('titulo'))->ignore($cancion?->id),
            ],
            'genero_id' => ['required', 'exists:generos,id'],
        ], ['artista_original.unique' => 'Esa canción de ese artista ya existe en el catálogo.'], ['genero_id' => 'estilo']);
    }
}
