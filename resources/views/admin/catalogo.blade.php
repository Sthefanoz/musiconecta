@extends('layouts.app')
@section('title', $cfg['titulo'])

@section('content')
<h1 class="titulo">Catálogos</h1>
<nav class="mt-6 flex flex-wrap gap-8 border-b border-slate-200">
    @foreach (\App\Http\Controllers\Admin\CatalogoController::CATALOGOS as $clave => $c)
        <a href="{{ route('admin.catalogos.index', $clave) }}" class="tab {{ $catalogo === $clave ? 'tab-activa' : '' }}">{{ $c['titulo'] }}</a>
    @endforeach
</nav>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <h2 class="text-lg font-bold">{{ $cfg['titulo'] }} ({{ $items->count() }})</h2>
        <table class="tabla mt-3">
            <thead><tr><th>Nombre</th><th></th></tr></thead>
            <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>
                        <form method="POST" action="{{ route('admin.catalogos.update', [$catalogo, $item->id]) }}" class="flex gap-2">
                            @csrf @method('PUT')
                            <input class="input" name="nombre" value="{{ $item->nombre }}" required>
                            <button class="btn btn-light btn-sm">Guardar</button>
                        </form>
                    </td>
                    <td class="w-24 text-right">
                        <form method="POST" action="{{ route('admin.catalogos.destroy', [$catalogo, $item->id]) }}" onsubmit="return confirm('¿Eliminar «{{ $item->nombre }}»?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:underline">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('admin.catalogos.store', $catalogo) }}" class="card h-fit space-y-4">
        @csrf
        <h2 class="text-lg font-bold">Nuevo {{ $cfg['singular'] }}</h2>
        <input class="input" name="nombre" placeholder="Nombre" required>
        <button class="btn btn-primary">Crear</button>
    </form>
</div>
@endsection
