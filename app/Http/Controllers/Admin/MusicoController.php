<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\Genero;
use App\Models\Musico;
use App\Models\User;
use App\Rules\CedulaEcuatoriana;
use App\Support\Ubicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Administración de músicos. Alimenta al Core: el género principal y la ciudad definen el grupo
 * con el que se compara a cada músico, y la cédula identifica a quien recibe el reparto del pago.
 */
class MusicoController extends Controller
{
    public function index(Request $request): View
    {
        $musicos = Musico::with(['user', 'ciudad.provincia', 'generoPrincipal'])
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('nombre_artistico', 'like', "%{$v}%")->orWhere('cedula', 'like', "%{$v}%")))
            ->when($request->query('estado') === 'pendientes', fn ($q) => $q->where('verificado', false))
            ->orderBy('verificado')->orderBy('nombre_artistico')
            ->paginate(25)->withQueryString();

        return view('admin.musicos.index', compact('musicos'));
    }

    public function create(): View
    {
        return $this->formulario(new Musico(['anios_experiencia' => 0]));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        DB::transaction(function () use ($datos) {
            $user = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'] ?? null,
                'password' => $datos['password'],
                'rol' => Rol::Musico,
            ]);
            $musico = Musico::create(['user_id' => $user->id] + $this->camposMusico($datos));
            $musico->generos()->sync($this->generos($datos));
        });

        return redirect()->route('admin.musicos.index')->with('ok', 'Músico '.$datos['nombre_artistico'].' registrado.');
    }

    public function edit(Musico $musico): View
    {
        return $this->formulario($musico->load('user', 'generos'));
    }

    public function update(Request $request, Musico $musico): RedirectResponse
    {
        $datos = $this->validar($request, $musico);

        DB::transaction(function () use ($musico, $datos) {
            $usuario = ['name' => $datos['name'], 'email' => $datos['email'], 'telefono' => $datos['telefono'] ?? null];
            if (! empty($datos['password'])) {
                $usuario['password'] = $datos['password']; // solo si el admin escribió una nueva
            }
            $musico->user->update($usuario);
            $musico->update($this->camposMusico($datos));
            $musico->generos()->sync($this->generos($datos));
        });

        return redirect()->route('admin.musicos.index')->with('ok', 'Músico '.$musico->nombre_artistico.' actualizado.');
    }

    /** Verifica (o retira la verificación) para mostrar la insignia en el perfil. */
    public function verificar(Musico $musico): RedirectResponse
    {
        $musico->update(['verificado' => ! $musico->verificado]);

        return back()->with('ok', $musico->nombre_artistico.($musico->verificado ? ' ahora está verificado.' : ' ya no está verificado.'));
    }

    private function formulario(Musico $musico): View
    {
        return view('admin.musicos.form', [
            'musico' => $musico,
            'generos' => Genero::orderBy('nombre')->get(),
        ]);
    }

    /**
     * Validación en BACK-END antes de grabar en la base de datos.
     * La cédula (dato sensible) se valida con el algoritmo módulo 10 y debe ser única.
     */
    private function validar(Request $request, ?Musico $musico = null): array
    {
        // Se normaliza: sin espacios ni guiones.
        $request->merge(['cedula' => preg_replace('/[\s-]/', '', (string) $request->input('cedula'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($musico?->user_id)],
            'password' => [$musico ? 'nullable' : 'required', Password::min(8)],
            'telefono' => ['nullable', 'regex:/^0\d{9}$/'],
            'cedula' => ['required', new CedulaEcuatoriana, Rule::unique('musicos', 'cedula')->ignore($musico?->id)],
            'nombre_artistico' => ['required', 'string', 'max:150'],
            'biografia' => ['nullable', 'string', 'max:3000'],
            'anios_experiencia' => ['required', 'integer', 'min:0', 'max:80'],
            'genero_principal_id' => ['required', 'exists:generos,id'],
            'generos' => ['nullable', 'array'],
            'generos.*' => ['integer', 'exists:generos,id'],
            'verificado' => ['nullable', 'boolean'],
            ...Ubicacion::reglas($request),
        ], [
            'cedula.unique' => 'Ya existe un músico registrado con esa cédula.',
            'telefono.regex' => 'El teléfono debe tener 10 dígitos y empezar con 0 (ej.: 0991234567).',
            ...Ubicacion::mensajes(),
        ], ['cedula' => 'cédula', 'genero_principal_id' => 'género principal', 'ciudad_id' => 'ciudad', 'provincia_id' => 'provincia']);
    }

    private function camposMusico(array $datos): array
    {
        return [
            'cedula' => $datos['cedula'],
            'nombre_artistico' => $datos['nombre_artistico'],
            'biografia' => $datos['biografia'] ?? null,
            'anios_experiencia' => $datos['anios_experiencia'],
            'genero_principal_id' => $datos['genero_principal_id'],
            'ciudad_id' => $datos['ciudad_id'],
            'verificado' => (bool) ($datos['verificado'] ?? false),
        ];
    }

    private function generos(array $datos): array
    {
        return array_unique([...($datos['generos'] ?? []), (int) $datos['genero_principal_id']]);
    }
}
