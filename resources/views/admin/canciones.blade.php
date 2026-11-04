@extends('layouts.app')
@section('title', 'Canciones')

@section('content')
<h1 class="titulo">Catálogo de canciones</h1>
<p class="subtitulo">El estilo de cada canción alimenta la comparativa "Repertorio solicitado": el ingreso de cada contratación se reparte entre los estilos pedidos.</p>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <input class="input w-64" name="q" value="{{ request('q') }}" placeholder="Título o artista">
            <select class="input w-48" name="genero_id">
                <option value="">Todos los estilos</option>
                @foreach ($generos as $g)<option value="{{ $g->id }}" @selected(request('genero_id') == $g->id)>{{ $g->nombre }}</option>@endforeach
            </select>
            <button class="btn btn-primary">Filtrar</button>
        </form>
        <table class="tabla mt-4">
            <thead><tr><th>Título · Artista · Estilo</th><th class="text-right">Pedidos</th><th></th></tr></thead>
            <tbody>
            @foreach ($canciones as $c)
                <tr>
                    <td>
                        <form method="POST" action="{{ route('admin.canciones.update', $c) }}" class="grid gap-2 sm:grid-cols-[1fr_1fr_10rem_auto]">
                            @csrf @method('PUT')
                            <input class="input" name="titulo" value="{{ $c->titulo }}" required>
                            <input class="input" name="artista_original" value="{{ $c->artista_original }}">
                            <select class="input" name="genero_id">
                                @foreach ($generos as $g)<option value="{{ $g->id }}" @selected($c->genero_id === $g->id)>{{ $g->nombre }}</option>@endforeach
                            </select>
                            <button class="btn btn-light btn-sm">Guardar</button>
                        </form>
                    </td>
                    <td class="text-right">{{ $c->pedidos }}</td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('admin.canciones.destroy', $c) }}" onsubmit="return confirm('¿Eliminar {{ addslashes($c->titulo) }}?')">@csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:underline">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $canciones->links() }}</div>
    </div>

    <form method="POST" action="{{ route('admin.canciones.store') }}" class="card h-fit space-y-4">
        @csrf
        <h2 class="text-lg font-bold">Nueva canción</h2>
        <div><label class="label">Título</label><input class="input" name="titulo" value="{{ old('titulo') }}" required></div>
        <div><label class="label">Artista original</label><input class="input" name="artista_original" value="{{ old('artista_original') }}"></div>
        <div>
            <label class="label">Estilo</label>
            <select class="input" name="genero_id" required>
                <option value="">Selecciona un estilo…</option>
                @foreach ($generos as $g)<option value="{{ $g->id }}" @selected(old('genero_id') == $g->id)>{{ $g->nombre }}</option>@endforeach
            </select>
        </div>
        <button class="btn btn-primary">Agregar</button>
    </form>
</div>
@endsection
