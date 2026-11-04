{{-- Formulario para agregar una canción del catálogo o una nueva. Requiere $accion, $catalogo, $generos --}}
<div class="grid gap-6 md:grid-cols-2">
    <form method="POST" action="{{ $accion }}" class="space-y-3">
        @csrf
        <p class="text-sm font-semibold">Del catálogo</p>
        <select name="cancion_id" class="input" required>
            @foreach ($catalogo as $c)<option value="{{ $c->id }}">{{ $c->titulo }} · {{ $c->genero->nombre }}</option>@endforeach
        </select>
        <button class="btn btn-primary btn-sm" @disabled($catalogo->isEmpty())>Agregar</button>
    </form>
    <form method="POST" action="{{ $accion }}" class="space-y-3">
        @csrf
        <p class="text-sm font-semibold">Nueva canción</p>
        <input class="input" name="titulo" placeholder="Título" required>
        <div class="grid grid-cols-2 gap-2">
            <input class="input" name="artista_original" placeholder="Artista original">
            <select name="genero_id" class="input" required>
                <option value="">Estilo…</option>
                @foreach ($generos as $g)<option value="{{ $g->id }}">{{ $g->nombre }}</option>@endforeach
            </select>
        </div>
        <button class="btn btn-primary btn-sm">Crear y agregar</button>
    </form>
</div>
