@extends('layouts.app')
@section('title', 'Agenda')

@section('content')
<h1 class="titulo">Agenda</h1>
<p class="subtitulo">Bloquea fechas y horarios en los que no estás disponible. Las contrataciones aceptadas y confirmadas bloquean tu agenda automáticamente.</p>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <h2 class="text-lg font-bold">Próximos bloqueos</h2>
        <table class="tabla mt-3">
            <thead><tr><th>Desde</th><th>Hasta</th><th>Motivo</th><th>Origen</th><th></th></tr></thead>
            <tbody>
            @forelse ($bloqueos as $b)
                <tr>
                    <td class="whitespace-nowrap">{{ $b->inicio->format('d/m/Y H:i') }}</td>
                    <td class="whitespace-nowrap">{{ $b->fin->format('d/m/Y H:i') }}</td>
                    <td>{{ $b->motivo }}</td>
                    <td>
                        @if ($b->contratacion_id)
                            <a href="{{ route('contrataciones.show', $b->contratacion_id) }}" class="badge bg-indigo-100 text-indigo-700">Contratación</a>
                        @else
                            <span class="badge bg-slate-200 text-slate-600">Manual</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @unless ($b->contratacion_id)
                            <form method="POST" action="{{ route('musico.agenda.destroy', $b) }}">@csrf @method('DELETE')
                                <button class="text-xs text-red-600 hover:underline">Quitar</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-slate-500">Tu agenda está libre.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('musico.agenda.store') }}" class="card h-fit space-y-4">
        @csrf
        <h2 class="text-lg font-bold">Bloquear horario</h2>
        <div>
            <label class="label">Fecha</label>
            <input class="input" type="date" name="fecha" min="{{ now()->toDateString() }}" value="{{ old('fecha') }}" required>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="todo_el_dia" value="1" class="accent-marca-600" @checked(old('todo_el_dia'))> Todo el día</label>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="label">Desde</label><input class="input" type="time" name="hora_inicio" value="{{ old('hora_inicio') }}"></div>
            <div><label class="label">Hasta</label><input class="input" type="time" name="hora_fin" value="{{ old('hora_fin') }}"></div>
        </div>
        <div>
            <label class="label">Motivo</label>
            <input class="input" name="motivo" value="{{ old('motivo') }}" placeholder="Ej.: Viaje">
        </div>
        <button class="btn btn-primary">Bloquear</button>
    </form>
</div>
@endsection
