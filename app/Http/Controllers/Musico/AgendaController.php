<?php

namespace App\Http\Controllers\Musico;

use App\Http\Controllers\Controller;
use App\Models\BloqueoAgenda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Agenda: bloqueos manuales + bloqueos automáticos generados por contrataciones aceptadas/confirmadas. */
class AgendaController extends Controller
{
    public function index(Request $request): View
    {
        $bloqueos = $request->user()->musico->bloqueos()
            ->with('contratacion.servicio')
            ->where('fin', '>=', now()->startOfDay())
            ->orderBy('inicio')
            ->get();

        return view('musico.agenda', compact('bloqueos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'todo_el_dia' => ['nullable', 'boolean'],
            'hora_inicio' => ['nullable', 'required_unless:todo_el_dia,1', 'date_format:H:i'],
            'hora_fin' => ['nullable', 'required_unless:todo_el_dia,1', 'date_format:H:i'],
            'motivo' => ['nullable', 'string', 'max:150'],
        ]);

        if ($request->boolean('todo_el_dia')) {
            $inicio = Carbon::parse($datos['fecha'])->startOfDay();
            $fin = Carbon::parse($datos['fecha'])->endOfDay();
        } else {
            $inicio = Carbon::parse($datos['fecha'].' '.$datos['hora_inicio']);
            $fin = Carbon::parse($datos['fecha'].' '.$datos['hora_fin']);
            if ($fin->lte($inicio)) {
                throw ValidationException::withMessages(['hora_fin' => 'La hora de fin debe ser posterior a la de inicio.']);
            }
        }

        $request->user()->musico->bloqueos()->create([
            'inicio' => $inicio,
            'fin' => $fin,
            'motivo' => $datos['motivo'] ?: 'No disponible',
        ]);

        return back()->with('ok', 'Horario bloqueado.');
    }

    public function destroy(Request $request, BloqueoAgenda $bloqueo): RedirectResponse
    {
        abort_unless($bloqueo->musico_id === $request->user()->musico->id, 403);
        if ($bloqueo->contratacion_id) {
            return back()->with('error', 'Este bloqueo pertenece a una contratación; no se puede quitar manualmente.');
        }

        $bloqueo->delete();

        return back()->with('ok', 'Bloqueo eliminado.');
    }
}
