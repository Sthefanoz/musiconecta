<?php

namespace App\Http\Controllers\Musico;

use App\Http\Controllers\Controller;
use App\Models\Cancion;
use App\Models\Genero;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RepertorioController extends Controller
{
    public function index(Request $request): View
    {
        $musico = $request->user()->musico;

        return view('musico.repertorio', [
            'canciones' => $musico->repertorio()->with('genero')->orderBy('titulo')->get(),
            'catalogo' => Cancion::with('genero')->whereNotIn('id', $musico->repertorio()->pluck('canciones.id'))->orderBy('titulo')->get(),
            'generos' => Genero::orderBy('nombre')->get(),
            'accion' => route('musico.repertorio.store'),
            'titulo' => 'Mi repertorio',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cancion = self::resolverCancion($request);
        $request->user()->musico->repertorio()->syncWithoutDetaching([$cancion->id]);

        return back()->with('ok', '"'.$cancion->titulo.'" agregada a tu repertorio.');
    }

    public function destroy(Request $request, Cancion $cancion): RedirectResponse
    {
        $request->user()->musico->repertorio()->detach($cancion->id);

        return back()->with('ok', '"'.$cancion->titulo.'" quitada de tu repertorio.');
    }

    /** Toma una canción del catálogo o crea una nueva (título + artista + estilo). */
    public static function resolverCancion(Request $request): Cancion
    {
        $datos = $request->validate([
            'cancion_id' => ['nullable', 'required_without:titulo', 'exists:canciones,id'],
            'titulo' => ['nullable', 'required_without:cancion_id', 'string', 'max:150'],
            'artista_original' => ['nullable', 'string', 'max:150'],
            'genero_id' => ['nullable', 'required_with:titulo', 'exists:generos,id'],
        ]);

        if (! empty($datos['cancion_id'])) {
            return Cancion::findOrFail($datos['cancion_id']);
        }

        return Cancion::firstOrCreate(
            ['titulo' => trim($datos['titulo']), 'artista_original' => $datos['artista_original'] ?? null],
            ['genero_id' => $datos['genero_id']],
        );
    }
}
