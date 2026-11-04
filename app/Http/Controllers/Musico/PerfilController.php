<?php

namespace App\Http\Controllers\Musico;

use App\Http\Controllers\Controller;
use App\Models\Ciudad;
use App\Models\Genero;
use App\Models\Musico;
use App\Rules\CedulaEcuatoriana;
use App\Support\Ubicacion;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('musico.perfil', [
            'musico' => $request->user()->musico?->load('generos'),
            'user' => $request->user(),
            'generos' => Genero::orderBy('nombre')->get(),
            'ciudades' => Ciudad::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $nuevo = ! $user->musico;

        $request->merge(['cedula' => preg_replace('/[\s-]/', '', (string) $request->input('cedula'))]);
        $datos = $request->validate([
            'cedula' => ['required', new CedulaEcuatoriana, Rule::unique('musicos', 'cedula')->ignore($user->musico?->id)],
            'nombre_artistico' => ['required', 'string', 'max:150'],
            'biografia' => ['nullable', 'string', 'max:3000'],
            'anios_experiencia' => ['required', 'integer', 'min:0', 'max:80'],
            'genero_principal_id' => ['required', 'exists:generos,id'],
            'generos' => ['nullable', 'array'],
            'generos.*' => ['integer', 'exists:generos,id'],
            'telefono' => ['nullable', 'regex:/^0\d{9}$/'],
            ...Ubicacion::reglas($request),
        ], [
            'cedula.unique' => 'Ya existe un músico registrado con esa cédula.',
            'telefono.regex' => 'El teléfono debe tener 10 dígitos y empezar con 0.',
            ...Ubicacion::mensajes(),
        ], ['cedula' => 'cédula']);
        unset($datos['provincia_id']);

        DB::transaction(function () use ($user, $datos) {
            $user->update(['telefono' => $datos['telefono'] ?? null]);
            $musico = Musico::updateOrCreate(['user_id' => $user->id], collect($datos)->except(['generos', 'telefono'])->all());
            // El género principal siempre forma parte de los géneros que interpreta.
            $musico->generos()->sync(array_unique([...($datos['generos'] ?? []), $datos['genero_principal_id']]));
        });

        return $nuevo
            ? redirect()->route('musico.servicios.create')->with('ok', 'Perfil creado. Ahora publica tu primer servicio.')
            : back()->with('ok', 'Perfil actualizado.');
    }
}
