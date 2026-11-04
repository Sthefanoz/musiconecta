@extends('layouts.app')
@section('title', $banda->nombre)

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="titulo">{{ $banda->nombre }}</h1>
        <p class="subtitulo">Líder: {{ $banda->lider->nombre_artistico }}</p>
    </div>
    <a href="{{ route('bandas.show', $banda) }}" class="btn btn-light">Ver perfil público</a>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    {{-- Integrantes y porcentajes --}}
    <div class="card lg:col-span-2">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">Integrantes y reparto del pago</h2>
            <span class="badge {{ abs($suma - 100) < 0.001 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">Suma: {{ pct($suma, 2) }}</span>
        </div>
        @if (abs($suma - 100) >= 0.001)
            <p class="mt-2 rounded-lg bg-amber-100 px-3 py-2 text-sm text-amber-800">Los porcentajes deben sumar 100 % para que la banda pueda recibir solicitudes.</p>
        @endif

        <form method="POST" action="{{ route('musico.bandas.integrantes.update', $banda) }}" id="form-integrantes">
            @csrf @method('PUT')
        </form>
        <table class="tabla mt-4">
            <thead><tr><th>Músico</th><th>Correo</th><th>Instrumento</th><th class="w-36">Porcentaje</th><th></th></tr></thead>
            <tbody>
            @foreach ($banda->integrantes as $m)
                <tr>
                    <td class="font-medium">{{ $m->nombre_artistico }} @if ($banda->lider_id === $m->id)<span class="badge bg-marca-100 text-marca-700">Líder</span>@endif</td>
                    <td class="text-slate-500">{{ $m->user->email }}</td>
                    <td>
                        @if ($esLider)
                            <input form="form-integrantes" class="input" name="integrantes[{{ $m->id }}][instrumento]" value="{{ old("integrantes.$m->id.instrumento", $m->pivot->instrumento) }}" required>
                        @else {{ $m->pivot->instrumento }} @endif
                    </td>
                    <td>
                        @if ($esLider)
                            <div class="flex items-center gap-1"><input form="form-integrantes" class="input" type="number" step="0.01" min="0" max="100" name="integrantes[{{ $m->id }}][porcentaje_pago]" value="{{ old("integrantes.$m->id.porcentaje_pago", $m->pivot->porcentaje_pago + 0) }}" required> %</div>
                        @else {{ pct($m->pivot->porcentaje_pago, 2) }} @endif
                    </td>
                    <td class="text-right">
                        @if ($esLider && $banda->lider_id !== $m->id)
                            <form method="POST" action="{{ route('musico.bandas.integrantes.destroy', [$banda, $m]) }}" onsubmit="return confirm('¿Quitar a {{ $m->nombre_artistico }}?')">@csrf @method('DELETE')
                                <button class="text-xs text-red-600 hover:underline">Quitar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @if ($esLider)
            <button form="form-integrantes" class="btn btn-primary mt-4">Guardar porcentajes</button>

            <form method="POST" action="{{ route('musico.bandas.integrantes.store', $banda) }}" class="mt-6 grid gap-3 border-t border-slate-100 pt-6 sm:grid-cols-4">
                @csrf
                <div class="sm:col-span-2">
                    <label class="label">Agregar integrante (correo del músico registrado)</label>
                    <input class="input" type="email" name="email" required>
                </div>
                <div>
                    <label class="label">Instrumento</label>
                    <input class="input" name="instrumento" required>
                </div>
                <div class="flex items-end"><button class="btn btn-light w-full">Agregar</button></div>
            </form>
        @endif
    </div>

    @if ($esLider)
        <div class="card">
            <h2 class="text-lg font-bold">Datos de la banda</h2>
            <form method="POST" action="{{ route('musico.bandas.update', $banda) }}" class="mt-4 space-y-4">
                @csrf @method('PUT')
                @include('musico.bandas._campos', ['banda' => $banda])
                <button class="btn btn-primary">Guardar</button>
            </form>
        </div>
    @endif

    <div class="card {{ $esLider ? '' : 'lg:col-span-2' }}">
        <h2 class="text-lg font-bold">Repertorio de la banda ({{ $banda->repertorio->count() }})</h2>
        <ul class="mt-3 max-h-72 space-y-1 overflow-y-auto text-sm">
            @forelse ($banda->repertorio->sortBy('titulo') as $c)
                <li class="flex items-center justify-between rounded px-2 py-1 hover:bg-slate-50">
                    <span>{{ $c->titulo }} <span class="text-slate-400">· {{ $c->genero->nombre }}</span></span>
                    @if ($esLider)
                        <form method="POST" action="{{ route('musico.bandas.repertorio.destroy', [$banda, $c]) }}">@csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:underline">Quitar</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="text-slate-500">Sin canciones.</li>
            @endforelse
        </ul>
        @if ($esLider)
            <div class="mt-6 border-t border-slate-100 pt-4">
                @include('musico._agregar_cancion', ['accion' => route('musico.bandas.repertorio.store', $banda)])
            </div>
        @endif
    </div>
</div>
@endsection
