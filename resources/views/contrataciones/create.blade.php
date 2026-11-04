@extends('layouts.app')
@section('title', 'Solicitar contratación')

@section('content')
@php
    $horasDef = old('horas', $servicio->duracion_horas + 0);
    $montoDef = $servicio->cotizar((float) $horasDef);
@endphp
<form method="POST" action="{{ route('contrataciones.store', $servicio) }}"
      data-disponibilidad="{{ route('contrataciones.disponibilidad', $servicio) }}"
      class="grid gap-6 lg:grid-cols-3">
    @csrf
    <div class="card lg:col-span-2">
        <h1 class="text-2xl font-bold">Solicitar contratación</h1>
        <p class="subtitulo">
            {{ $servicio->proveedorNombre() }}
            @if ($servicio->esDeBanda()) · {{ $servicio->banda->integrantes->count() }} integrantes @endif
            @if ($calificacion) · ★ {{ number_format($calificacion, 1, ',', '.') }} ({{ $cantidadResenas }} reseñas) @endif
            @if ($verificado) <span class="badge ml-1 bg-green-100 text-green-700">Verificado</span> @endif
        </p>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label class="label">Servicio</label>
                <input class="input bg-slate-50" value="{{ $servicio->nombre }} · {{ $servicio->proveedorNombre() }} · {{ dinero($servicio->precio_base) }} ({{ $servicio->duracion_horas + 0 }} h)" disabled>
            </div>
            <div>
                <label class="label" for="tipo_evento_id">Tipo de evento</label>
                <select class="input" name="tipo_evento_id" id="tipo_evento_id" required>
                    @foreach ($tiposEvento as $t)<option value="{{ $t->id }}" @selected(old('tipo_evento_id') == $t->id)>{{ $t->nombre }}</option>@endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div>
                <label class="label" for="fecha_evento">Fecha</label>
                <input class="input" type="date" name="fecha_evento" id="fecha_evento" min="{{ now()->toDateString() }}" value="{{ old('fecha_evento', now()->addDays(7)->toDateString()) }}" required>
            </div>
            <div>
                <label class="label" for="hora_inicio">Hora de inicio</label>
                <input class="input" type="time" name="hora_inicio" id="hora_inicio" value="{{ old('hora_inicio', '18:00') }}" required>
            </div>
            <div>
                <label class="label" for="horas">Horas</label>
                <input class="input" type="number" step="0.5" min="0.5" max="12" name="horas" id="horas" value="{{ $horasDef }}" required>
            </div>
        </div>

        <div class="mt-4">
            @include('partials.ubicacion', ['ciudadId' => $ciudadDefecto])
        </div>

        <div class="mt-4">
            <label class="label" for="direccion">Dirección</label>
            <input class="input" name="direccion" id="direccion" value="{{ old('direccion') }}" placeholder="Ej.: Hacienda La Carriona, Sangolquí" required>
        </div>

        <div class="mt-6">
            <p class="label">Canciones que deseas ({{ $servicio->esDeBanda() ? 'repertorio de la banda' : 'repertorio del músico' }})</p>
            <div class="mt-2 grid gap-x-6 gap-y-2 sm:grid-cols-2">
                @forelse ($canciones as $c)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="canciones[]" value="{{ $c->id }}" class="size-4 rounded border-slate-300 accent-marca-600"
                               @checked(in_array($c->id, old('canciones', [])))>
                        {{ $c->titulo }} <span class="text-slate-400">· {{ $c->genero->nombre }}</span>
                    </label>
                @empty
                    <p class="text-sm text-slate-500">El músico aún no registró su repertorio.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card h-fit">
        <h2 class="text-xl font-bold">Resumen</h2>
        <div id="disponibilidad" class="mt-4 rounded-xl bg-slate-100 p-4 text-sm text-slate-600">Verificando disponibilidad…</div>
        <table class="mt-4 w-full text-sm">
            <tr class="border-b border-slate-100"><td class="py-3">{{ $servicio->nombre }} (<span id="resumen-horas">{{ $horasDef }}</span> h)</td><td class="py-3 text-right" id="resumen-monto">{{ dinero($montoDef, 2) }}</td></tr>
            <tr class="border-b border-slate-100"><td class="py-3">Canciones elegidas</td><td class="py-3 text-right" id="resumen-canciones">{{ count(old('canciones', [])) }}</td></tr>
            <tr class="border-b border-slate-100 font-bold"><td class="py-3">Total</td><td class="py-3 text-right" id="resumen-total">{{ dinero($montoDef, 2) }}</td></tr>
            <tr class="border-b border-slate-100"><td class="py-3">Anticipo ({{ config('musiconecta.anticipo') * 100 }} %) al ser aceptada</td><td class="py-3 text-right" id="resumen-anticipo">{{ dinero($montoDef * config('musiconecta.anticipo'), 2) }}</td></tr>
        </table>
        <button type="submit" class="btn btn-primary mt-5">Enviar solicitud</button>
        <p class="mt-4 text-xs text-slate-500">El músico tiene 48 horas para aceptar o rechazar.</p>
    </div>
</form>
@endsection
