@extends('layouts.app')
@section('title', $banda->nombre)

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="card">
            <h1 class="titulo">{{ $banda->nombre }}</h1>
            <p class="subtitulo">Banda · {{ $banda->genero->nombre }} · {{ $banda->ciudad->nombre }} · Líder: <a class="text-marca-600 hover:underline" href="{{ route('musicos.show', $banda->lider) }}">{{ $banda->lider->nombre_artistico }}</a></p>
            @if ($banda->descripcion)<p class="mt-4 leading-relaxed text-slate-700">{{ $banda->descripcion }}</p>@endif

            <h2 class="mt-6 text-sm font-semibold">Integrantes</h2>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($banda->integrantes as $m)
                    <a href="{{ route('musicos.show', $m) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm hover:bg-slate-200">{{ $m->nombre_artistico }} <span class="text-slate-400">· {{ $m->pivot->instrumento }}</span></a>
                @endforeach
            </div>
        </div>

        <div class="card">
            <h2 class="text-lg font-bold">Servicios</h2>
            @unless ($porcentajesValidos)
                <p class="mt-2 rounded-lg bg-amber-100 px-3 py-2 text-sm text-amber-800">La banda está definiendo sus porcentajes de pago; por ahora no recibe solicitudes.</p>
            @endunless
            <div class="mt-4 space-y-3">
                @forelse ($servicios as $s)
                    @include('perfiles._servicio', ['s' => $s])
                @empty
                    <p class="text-sm text-slate-500">No tiene servicios activos.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <h2 class="text-lg font-bold">Repertorio <span class="text-sm font-normal text-slate-400">({{ $banda->repertorio->count() }} canciones)</span></h2>
            <div class="mt-3 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
                @forelse ($banda->repertorio->sortBy('titulo') as $c)
                    <p>{{ $c->titulo }} <span class="text-slate-400">· {{ $c->genero->nombre }}</span></p>
                @empty
                    <p class="text-slate-500">Sin canciones registradas.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <h2 class="text-lg font-bold">Historial</h2>
            <dl class="mt-4 grid grid-cols-2 gap-4 text-center">
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Contrataciones realizadas</dt><dd class="text-2xl font-bold">{{ $totalRealizadas }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Calificación</dt><dd class="text-2xl font-bold">{{ $calificacion ? number_format($calificacion, 1, ',', '.').' ★' : '—' }}</dd><dd class="text-xs text-slate-400">{{ $cantidadResenas }} reseñas</dd></div>
            </dl>
        </div>
        @include('perfiles._resenas')
    </div>
</div>
@endsection
