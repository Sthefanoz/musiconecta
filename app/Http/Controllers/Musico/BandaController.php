<?php

namespace App\Http\Controllers\Musico;

use App\Http\Controllers\Controller;
use App\Models\Banda;
use App\Models\Cancion;
use App\Models\Ciudad;
use App\Models\Genero;
use App\Models\Musico;
use App\Models\User;
use App\Support\Ubicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Bandas: el líder las crea, agrega o quita integrantes registrados e indica instrumento y
 * porcentaje de pago (la suma debe ser 100 %). El líder también es integrante.
 */
class BandaController extends Controller
{
    public function index(Request $request): View
    {
        $musico = $request->user()->musico;

        return view('musico.bandas.index', [
            'bandas' => $musico->bandas()->with(['lider', 'genero', 'ciudad'])->withCount('integrantes')->get(),
            'musico' => $musico,
        ]);
    }

    public function create(): View
    {
        return view('musico.bandas.create', [
            'generos' => Genero::orderBy('nombre')->get(),
            'ciudades' => Ciudad::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $musico = $request->user()->musico;
        $datos = $this->validarBanda($request) + $request->validate(['instrumento' => ['required', 'string', 'max:80']]);

        $banda = DB::transaction(function () use ($musico, $datos) {
            $banda = Banda::create(['lider_id' => $musico->id] + collect($datos)->except('instrumento')->all());
            // El líder entra como integrante con el 100 % hasta que se agreguen más integrantes.
            $banda->integrantes()->attach($musico->id, ['instrumento' => $datos['instrumento'], 'porcentaje_pago' => 100]);

            return $banda;
        });

        return redirect()->route('musico.bandas.edit', $banda)->with('ok', 'Banda creada. Agrega a los integrantes y define sus porcentajes.');
    }

    public function edit(Request $request, Banda $banda): View
    {
        $musico = $request->user()->musico;
        abort_unless($banda->integrantes()->where('musicos.id', $musico->id)->exists(), 403);

        $banda->load(['integrantes.user', 'repertorio.genero', 'lider']);

        return view('musico.bandas.edit', [
            'banda' => $banda,
            'esLider' => $banda->esLider($musico),
            'suma' => $banda->integrantes->sum(fn ($m) => (float) $m->pivot->porcentaje_pago),
            'generos' => Genero::orderBy('nombre')->get(),
            'ciudades' => Ciudad::orderBy('nombre')->get(),
            'catalogo' => Cancion::with('genero')->whereNotIn('id', $banda->repertorio->pluck('id'))->orderBy('titulo')->get(),
        ]);
    }

    public function update(Request $request, Banda $banda): RedirectResponse
    {
        $this->soloLider($request, $banda);
        $banda->update($this->validarBanda($request));

        return back()->with('ok', 'Datos de la banda actualizados.');
    }

    /** Agrega un integrante registrado (por su correo) con 0 % para que el líder ajuste los porcentajes. */
    public function agregarIntegrante(Request $request, Banda $banda): RedirectResponse
    {
        $this->soloLider($request, $banda);
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'instrumento' => ['required', 'string', 'max:80'],
        ]);

        $nuevo = User::where('email', $datos['email'])->first()?->musico;
        if (! $nuevo) {
            throw ValidationException::withMessages(['email' => 'No existe un músico registrado con ese correo.']);
        }
        if ($banda->integrantes()->where('musicos.id', $nuevo->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'Ese músico ya es integrante de la banda.']);
        }

        $banda->integrantes()->attach($nuevo->id, ['instrumento' => $datos['instrumento'], 'porcentaje_pago' => 0]);

        return back()->with('ok', $nuevo->nombre_artistico.' fue agregado. Ajusta los porcentajes para que sumen 100 %.');
    }

    /** Guarda instrumentos y porcentajes de todos los integrantes a la vez (deben sumar 100 %). */
    public function actualizarIntegrantes(Request $request, Banda $banda): RedirectResponse
    {
        $this->soloLider($request, $banda);
        $datos = $request->validate([
            'integrantes' => ['required', 'array'],
            'integrantes.*.instrumento' => ['required', 'string', 'max:80'],
            'integrantes.*.porcentaje_pago' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $ids = $banda->integrantes()->pluck('musicos.id')->map(fn ($id) => (string) $id)->all();
        $filas = collect($datos['integrantes'])->only($ids);
        $suma = round($filas->sum(fn ($f) => (float) $f['porcentaje_pago']), 2);

        if (abs($suma - 100) > 0.001) {
            throw ValidationException::withMessages(['integrantes' => 'Los porcentajes deben sumar 100 % (suman '.pct($suma, 2).').']);
        }

        DB::transaction(function () use ($banda, $filas) {
            foreach ($filas as $musicoId => $fila) {
                $banda->integrantes()->updateExistingPivot($musicoId, [
                    'instrumento' => $fila['instrumento'],
                    'porcentaje_pago' => $fila['porcentaje_pago'],
                ]);
            }
        });

        return back()->with('ok', 'Integrantes y porcentajes guardados.');
    }

    public function quitarIntegrante(Request $request, Banda $banda, Musico $musico): RedirectResponse
    {
        $this->soloLider($request, $banda);
        if ($banda->lider_id === $musico->id) {
            return back()->with('error', 'El líder no puede salir de la banda.');
        }

        $banda->integrantes()->detach($musico->id);

        return back()->with('ok', $musico->nombre_artistico.' ya no es integrante. Revisa los porcentajes: deben volver a sumar 100 %.');
    }

    public function agregarCancion(Request $request, Banda $banda): RedirectResponse
    {
        $this->soloLider($request, $banda);
        $cancion = RepertorioController::resolverCancion($request);
        $banda->repertorio()->syncWithoutDetaching([$cancion->id]);

        return back()->with('ok', '"'.$cancion->titulo.'" agregada al repertorio de la banda.');
    }

    public function quitarCancion(Request $request, Banda $banda, Cancion $cancion): RedirectResponse
    {
        $this->soloLider($request, $banda);
        $banda->repertorio()->detach($cancion->id);

        return back()->with('ok', 'Canción quitada del repertorio de la banda.');
    }

    private function validarBanda(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'genero_id' => ['required', 'exists:generos,id'],
            ...Ubicacion::reglas($request),
        ], Ubicacion::mensajes());
        unset($datos['provincia_id']);

        return $datos;
    }

    private function soloLider(Request $request, Banda $banda): void
    {
        abort_unless($banda->esLider($request->user()->musico), 403, 'Solo el líder puede modificar la banda.');
    }
}
