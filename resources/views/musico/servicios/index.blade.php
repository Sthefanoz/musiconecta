@extends('layouts.app')
@section('title', 'Mis servicios')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="titulo">Mis servicios</h1>
        <p class="subtitulo">Lo que ofreces como solista o con las bandas que lideras.</p>
    </div>
    <a href="{{ route('musico.servicios.create') }}" class="btn btn-primary">+ Nuevo servicio</a>
</div>

<div class="card mt-6 overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Servicio</th><th>Tipo</th><th>Como</th><th class="text-right">Precio base</th><th class="text-right">Duración</th><th class="text-right">Contrataciones</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($servicios as $s)
            <tr @class(['opacity-60' => ! $s->activo])>
                <td class="font-medium">{{ $s->nombre }}</td>
                <td>{{ $s->tipo->nombre }}</td>
                <td>{{ $s->esDeBanda() ? $s->banda->nombre : 'Solista' }}</td>
                <td class="text-right">{{ dinero($s->precio_base, 2) }}</td>
                <td class="text-right">{{ $s->duracion_horas + 0 }} h</td>
                <td class="text-right">{{ $s->contrataciones_count }}</td>
                <td><span class="badge {{ $s->activo ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-600' }}">{{ $s->activo ? 'Activo' : 'Inactivo' }}</span></td>
                <td class="flex gap-2">
                    <a href="{{ route('musico.servicios.edit', $s) }}" class="btn btn-light btn-sm">Editar</a>
                    <form method="POST" action="{{ route('musico.servicios.toggle', $s) }}">@csrf @method('PATCH')
                        <button class="btn btn-light btn-sm">{{ $s->activo ? 'Desactivar' : 'Activar' }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="py-8 text-center text-slate-500">Aún no publicas servicios.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
