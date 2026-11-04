<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="label">Nombre de la banda</label>
        <input class="input" name="nombre" value="{{ old('nombre', $banda?->nombre) }}" required>
    </div>
    <div>
        <label class="label">Género</label>
        <select class="input" name="genero_id" required>
            @foreach ($generos as $g)<option value="{{ $g->id }}" @selected(old('genero_id', $banda?->genero_id) == $g->id)>{{ $g->nombre }}</option>@endforeach
        </select>
    </div>
    <div class="sm:col-span-2">
        @include('partials.ubicacion', ['ciudadId' => $banda?->ciudad_id])
    </div>
    <div class="sm:col-span-2">
        <label class="label">Descripción</label>
        <textarea class="input" name="descripcion" rows="3">{{ old('descripcion', $banda?->descripcion) }}</textarea>
    </div>
</div>
