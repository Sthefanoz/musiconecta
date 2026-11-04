@extends('layouts.app')
@section('title', $musico->nombre_artistico)

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="card">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="titulo">{{ $musico->nombre_artistico }}</h1>
                @if ($musico->verificado)<span class="badge bg-green-100 text-green-700">✓ Verificado</span>@endif
            </div>
            <p class="subtitulo">{{ $musico->generoPrincipal->nombre }} · {{ $musico->ciudad->nombre }} · {{ $musico->anios_experiencia }} años de experiencia</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($musico->generos as $g)<span class="badge bg-slate-100 text-slate-600">{{ $g->nombre }}</span>@endforeach
            </div>
            @if ($musico->biografia)<p class="mt-4 leading-relaxed text-slate-700">{{ $musico->biografia }}</p>@endif
        </div>

        <div class="card">
            <h2 class="text-lg font-bold">Servicios</h2>
            <div class="mt-4 space-y-3">
                @forelse ($servicios as $s)
                    @include('perfiles._servicio', ['s' => $s])
                @empty
                    <p class="text-sm text-slate-500">No tiene servicios activos como solista.</p>
                @endforelse
                @foreach ($serviciosBanda as $s)
                    @include('perfiles._servicio', ['s' => $s, 'conBanda' => true])
                @endforeach
            </div>
        </div>

        <div class="card">
            <h2 class="text-lg font-bold">Repertorio <span class="text-sm font-normal text-slate-400">({{ $musico->repertorio->count() }} canciones)</span></h2>
            <div class="mt-3 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
                @forelse ($musico->repertorio->sortBy('titulo') as $c)
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
            @if ($musico->bandas->isNotEmpty())
                <h3 class="mt-5 text-sm font-semibold">Bandas</h3>
                <ul class="mt-2 space-y-1 text-sm">
                    @foreach ($musico->bandas as $b)
                        <li><a href="{{ route('bandas.show', $b) }}" class="text-marca-600 hover:underline">{{ $b->nombre }}</a> <span class="text-slate-400">· {{ $b->pivot->instrumento }}</span></li>
                    @endforeach
                </ul>
            @endif
        </div>
        @include('perfiles._resenas')
    </div>
</div>
@endsection
