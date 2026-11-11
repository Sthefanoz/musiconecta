@extends('layouts.app')
@section('title', 'Músicos')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="titulo">Músicos</h1>
        <p class="subtitulo">Su género principal y su ciudad definen el grupo con el que el Core los compara; la cédula identifica a quién se paga cada reparto.</p>
    </div>
    <a href="{{ route('admin.musicos.create') }}" class="btn btn-primary">+ Nuevo músico</a>
</div>

<form method="GET" class="mt-6 flex flex-wrap items-end gap-3">
    <input class="input w-72" name="q" value="{{ request('q') }}" placeholder="Nombre artístico o cédula">
    <select class="input w-48" name="estado">
        <option value="">Todos</option>
        <option value="pendientes" @selected(request('estado') === 'pendientes')>Sin verificar</option>
    </select>
    <button class="btn btn-primary">Filtrar</button>
</form>

<div class="card mt-6 overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Nombre artístico</th><th>Cédula</th><th>Correo</th><th>Género principal</th><th>Ciudad</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @foreach ($musicos as $m)
            <tr>
                <td><a href="{{ route('musicos.show', $m) }}" class="font-medium hover:text-marca-600">{{ $m->nombre_artistico }}</a></td>
                <td class="font-mono text-xs">{{ $m->cedula }}</td>
                <td class="text-slate-500">{{ $m->user->email }}</td>
                <td>{{ $m->generoPrincipal->nombre }}</td>
                <td>{{ $m->ciudad->nombre }} <span class="text-slate-400">· {{ $m->ciudad->provincia->nombre }}</span></td>
                <td><span class="badge {{ $m->verificado ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-600' }}">{{ $m->verificado ? 'Verificado' : 'Sin verificar' }}</span></td>
                <td>
                    <div class="flex justify-end gap-2">
                        <a href="{{ route('admin.musicos.edit', $m) }}" class="btn btn-light btn-sm">Editar</a>
                        <form method="POST" action="{{ route('admin.musicos.verificar', $m) }}">@csrf @method('PATCH')
                            <button class="btn btn-sm {{ $m->verificado ? 'btn-light' : 'btn-success' }}">{{ $m->verificado ? 'Quitar verificación' : 'Verificar' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.musicos.destroy', $m) }}" onsubmit="return confirm('¿Eliminar a {{ addslashes($m->nombre_artistico) }}?')">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-light text-red-700">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="mt-4">{{ $musicos->links() }}</div>
</div>
@endsection
