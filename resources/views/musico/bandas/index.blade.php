@extends('layouts.app')
@section('title', 'Mis bandas')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="titulo">Mis bandas</h1>
        <p class="subtitulo">Bandas que lideras o en las que participas como integrante.</p>
    </div>
    <a href="{{ route('musico.bandas.create') }}" class="btn btn-primary">+ Crear banda</a>
</div>

<div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
    @forelse ($bandas as $b)
        <div class="card">
            <div class="flex items-start justify-between">
                <h2 class="text-lg font-bold">{{ $b->nombre }}</h2>
                @if ($b->esLider($musico))<span class="badge bg-marca-100 text-marca-700">Líder</span>@endif
            </div>
            <p class="text-sm text-slate-500">{{ $b->genero->nombre }} · {{ $b->ciudad->nombre }} · {{ $b->integrantes_count }} integrantes</p>
            <p class="mt-3 text-sm">Tu rol: <b>{{ $b->pivot->instrumento }}</b> · tu parte: <b>{{ pct($b->pivot->porcentaje_pago, 0) }}</b></p>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('musico.bandas.edit', $b) }}" class="btn btn-light btn-sm">{{ $b->esLider($musico) ? 'Gestionar' : 'Ver' }}</a>
                <a href="{{ route('bandas.show', $b) }}" class="btn btn-light btn-sm">Perfil público</a>
            </div>
        </div>
    @empty
        <div class="card col-span-full text-center text-slate-500">Aún no formas parte de ninguna banda.</div>
    @endforelse
</div>
@endsection
