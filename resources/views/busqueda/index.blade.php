@extends('layouts.app')
@section('title', 'Buscar músicos')

@section('content')
<h1 class="titulo">Encuentra músicos para tu evento</h1>
<p class="subtitulo">Solistas y bandas con historial verificable de contrataciones y reseñas.</p>

<form method="GET" class="card mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-7">
    <div class="lg:col-span-2">
        <label class="label">Nombre</label>
        <input class="input" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Músico, banda o servicio">
    </div>
    <div>
        <label class="label">Género</label>
        <select class="input" name="genero_id">
            <option value="">Todos</option>
            @foreach ($generos as $g)<option value="{{ $g->id }}" @selected(($filtros['genero_id'] ?? '') == $g->id)>{{ $g->nombre }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="label">Ciudad</label>
        <select class="input" name="ciudad_id">
            <option value="">Todas</option>
            @foreach ($ciudades as $c)<option value="{{ $c->id }}" @selected(($filtros['ciudad_id'] ?? '') == $c->id)>{{ $c->nombre }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="label">Servicio</label>
        <select class="input" name="tipo_servicio_id">
            <option value="">Todos</option>
            @foreach ($tiposServicio as $t)<option value="{{ $t->id }}" @selected(($filtros['tipo_servicio_id'] ?? '') == $t->id)>{{ $t->nombre }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="label">Precio</label>
        <div class="flex gap-1">
            <input class="input" type="number" name="precio_min" min="0" placeholder="Mín" value="{{ $filtros['precio_min'] ?? '' }}">
            <input class="input" type="number" name="precio_max" min="0" placeholder="Máx" value="{{ $filtros['precio_max'] ?? '' }}">
        </div>
    </div>
    <div>
        <label class="label">Fecha libre</label>
        <input class="input" type="date" name="fecha" value="{{ $filtros['fecha'] ?? '' }}">
    </div>
    <div class="flex items-end gap-2 lg:col-span-7">
        <button class="btn btn-primary">Buscar</button>
        <a href="{{ route('home') }}" class="btn btn-light">Limpiar</a>
        <span class="ml-auto text-sm text-slate-500">{{ $proveedores->count() }} resultados</span>
    </div>
</form>

<div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
    @forelse ($proveedores as $p)
        <div class="card flex flex-col">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <a href="{{ $p->url }}" class="text-lg font-bold text-slate-900 hover:text-marca-600">{{ $p->nombre }}</a>
                    <p class="text-sm text-slate-500">
                        {{ $p->es_banda ? 'Banda · '.$p->integrantes.' integrantes' : 'Solista' }} · {{ $p->genero }} · {{ $p->ciudad }}
                    </p>
                </div>
                @if ($p->verificado)<span class="badge bg-green-100 text-green-700">Verificado</span>@endif
            </div>
            <ul class="mt-4 flex-1 space-y-2 text-sm">
                @foreach ($p->servicios as $s)
                    <li class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2">
                        <span>{{ $s->nombre }} <span class="text-slate-400">· {{ $s->duracion_horas + 0 }} h</span></span>
                        <span class="font-semibold">{{ dinero($s->precio_base) }}</span>
                    </li>
                @endforeach
            </ul>
            <a href="{{ $p->url }}" class="btn btn-light mt-4">Ver perfil e historial</a>
        </div>
    @empty
        <div class="card col-span-full text-center text-slate-500">No encontramos músicos con esos filtros.</div>
    @endforelse
</div>
@endsection
