<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genero;
use App\Models\Provincia;
use App\Models\TipoEvento;
use App\Models\TipoServicio;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** CRUD genérico de los catálogos: géneros, ciudades, tipos de servicio y tipos de evento. */
class CatalogoController extends Controller
{
    public const CATALOGOS = [
        'generos' => ['modelo' => Genero::class, 'titulo' => 'Géneros', 'singular' => 'género'],
        'provincias' => ['modelo' => Provincia::class, 'titulo' => 'Provincias', 'singular' => 'provincia'],
        'tipos-servicio' => ['modelo' => TipoServicio::class, 'titulo' => 'Tipos de servicio', 'singular' => 'tipo de servicio'],
        'tipos-evento' => ['modelo' => TipoEvento::class, 'titulo' => 'Tipos de evento', 'singular' => 'tipo de evento'],
    ];

    public function index(string $catalogo): View
    {
        $cfg = $this->config($catalogo);

        return view('admin.catalogo', [
            'catalogo' => $catalogo,
            'cfg' => $cfg,
            'items' => $cfg['modelo']::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request, string $catalogo): RedirectResponse
    {
        $cfg = $this->config($catalogo);
        $modelo = new $cfg['modelo'];
        $datos = $request->validate(['nombre' => ['required', 'string', 'max:100', Rule::unique($modelo->getTable(), 'nombre')]]);
        $cfg['modelo']::create($datos);

        return back()->with('ok', ucfirst($cfg['singular']).' creado.');
    }

    public function update(Request $request, string $catalogo, int $id): RedirectResponse
    {
        $cfg = $this->config($catalogo);
        $item = $cfg['modelo']::findOrFail($id);
        $datos = $request->validate(['nombre' => ['required', 'string', 'max:100', Rule::unique($item->getTable(), 'nombre')->ignore($id)]]);
        $item->update($datos);

        return back()->with('ok', ucfirst($cfg['singular']).' actualizado.');
    }

    public function destroy(string $catalogo, int $id): RedirectResponse
    {
        $cfg = $this->config($catalogo);

        try {
            $cfg['modelo']::findOrFail($id)->delete();
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar: hay registros que lo utilizan.');
        }

        return back()->with('ok', ucfirst($cfg['singular']).' eliminado.');
    }

    private function config(string $catalogo): array
    {
        abort_unless(isset(self::CATALOGOS[$catalogo]), 404);

        return self::CATALOGOS[$catalogo];
    }
}
