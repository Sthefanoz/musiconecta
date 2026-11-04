@extends('layouts.app')
@section('title', 'Mi repertorio')

@section('content')
<h1 class="titulo">Mi repertorio</h1>
<p class="subtitulo">Las canciones que ofreces como solista. Cada una con su estilo, que se usa en la comparativa de repertorio.</p>

<div class="mt-6 grid gap-6 lg:grid-cols-5">
    <div class="card lg:col-span-3">
        <h2 class="text-lg font-bold">{{ $canciones->count() }} canciones</h2>
        <table class="tabla mt-3">
            <thead><tr><th>Canción</th><th>Artista</th><th>Estilo</th><th></th></tr></thead>
            <tbody>
            @forelse ($canciones as $c)
                <tr>
                    <td class="font-medium">{{ $c->titulo }}</td>
                    <td class="text-slate-500">{{ $c->artista_original }}</td>
                    <td>{{ $c->genero->nombre }}</td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('musico.repertorio.destroy', $c) }}">@csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:underline">Quitar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-6 text-center text-slate-500">Agrega tus primeras canciones.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card h-fit lg:col-span-2">
        <h2 class="mb-4 text-lg font-bold">Agregar canción</h2>
        @include('musico._agregar_cancion')
    </div>
</div>
@endsection
