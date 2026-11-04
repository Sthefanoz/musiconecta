@extends('layouts.app')
@section('title', 'Ciudades')

@section('content')
<h1 class="titulo">Ciudades</h1>
<p class="subtitulo">Cada ciudad pertenece a una provincia. La ciudad del músico define con quién se compara en el Core.</p>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <form method="GET" class="flex items-end gap-2">
            <div class="flex-1">
                <label class="label">Filtrar por provincia</label>
                <select class="input" name="provincia_id" onchange="this.form.submit()">
                    <option value="">Todas las provincias</option>
                    @foreach ($provincias as $p)<option value="{{ $p->id }}" @selected(request('provincia_id') == $p->id)>{{ $p->nombre }}</option>@endforeach
                </select>
            </div>
        </form>
        <table class="tabla mt-4">
            <thead><tr><th>Provincia</th><th>Ciudad</th><th class="text-right">Músicos</th><th></th></tr></thead>
            <tbody>
            @foreach ($ciudades as $c)
                <tr>
                    <td colspan="2">
                        <form method="POST" action="{{ route('admin.ciudades.update', $c) }}" class="flex gap-2">
                            @csrf @method('PUT')
                            <select class="input w-56" name="provincia_id">
                                @foreach ($provincias as $p)<option value="{{ $p->id }}" @selected($c->provincia_id === $p->id)>{{ $p->nombre }}</option>@endforeach
                            </select>
                            <input class="input" name="nombre" value="{{ $c->nombre }}" required>
                            <button class="btn btn-light btn-sm">Guardar</button>
                        </form>
                    </td>
                    <td class="text-right">{{ $c->musicos_count }}</td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('admin.ciudades.destroy', $c) }}" onsubmit="return confirm('¿Eliminar {{ $c->nombre }}?')">@csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:underline">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.ciudades.store') }}" class="card h-fit space-y-4">
        @csrf
        <h2 class="text-lg font-bold">Nueva ciudad</h2>
        <div>
            <label class="label">Provincia</label>
            <select class="input" name="provincia_id" required>
                <option value="">Selecciona una provincia…</option>
                @foreach ($provincias as $p)<option value="{{ $p->id }}" @selected(old('provincia_id') == $p->id)>{{ $p->nombre }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label">Nombre</label>
            <input class="input" name="nombre" value="{{ old('nombre') }}" required>
        </div>
        <button class="btn btn-primary">Crear</button>
        <p class="text-xs text-slate-500">Las provincias se gestionan en <a class="text-marca-600 hover:underline" href="{{ route('admin.catalogos.index', 'provincias') }}">Catálogos → Provincias</a>.</p>
    </form>
</div>
@endsection
