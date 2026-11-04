@extends('layouts.app')
@section('title', 'Mi perfil')

@section('content')
<h1 class="titulo">{{ $musico ? 'Mi perfil de músico' : 'Completa tu perfil de músico' }}</h1>
<p class="subtitulo">Esta información aparece en tu perfil público y se usa para compararte con los músicos de tu género.</p>

@php $seleccionados = old('generos', $musico?->generos->pluck('id')->all() ?? []); @endphp
<form method="POST" action="{{ route('musico.perfil.update') }}" class="card mt-6 max-w-3xl space-y-4">
    @csrf @method('PUT')
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Nombre artístico</label>
            <input class="input" name="nombre_artistico" value="{{ old('nombre_artistico', $musico?->nombre_artistico ?? $user->name) }}" required>
        </div>
        <div>
            <label class="label">Teléfono</label>
            <input class="input" name="telefono" value="{{ old('telefono', $user->telefono) }}">
        </div>
        <div>
            <label class="label">Cédula</label>
            <input class="input font-mono" name="cedula" value="{{ old('cedula', $musico?->cedula) }}" maxlength="12" placeholder="1712345678" required>
            <p class="mt-1 text-xs text-slate-500">Necesaria para pagarte tu parte de cada contratación.</p>
        </div>
        <div>
            <label class="label">Años de experiencia</label>
            <input class="input" type="number" min="0" max="80" name="anios_experiencia" value="{{ old('anios_experiencia', $musico?->anios_experiencia ?? 0) }}" required>
        </div>
        <div class="sm:col-span-2">
            @include('partials.ubicacion', ['ciudadId' => $musico?->ciudad_id])
        </div>
        <div class="sm:col-span-2">
            <label class="label">Género principal</label>
            <select class="input" name="genero_principal_id" required>
                @foreach ($generos as $g)<option value="{{ $g->id }}" @selected(old('genero_principal_id', $musico?->genero_principal_id) == $g->id)>{{ $g->nombre }}</option>@endforeach
            </select>
        </div>
    </div>
    <div>
        <p class="label">Géneros que interpretas</p>
        <div class="grid gap-2 sm:grid-cols-3">
            @foreach ($generos as $g)
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="generos[]" value="{{ $g->id }}" class="accent-marca-600" @checked(in_array($g->id, $seleccionados))> {{ $g->nombre }}</label>
            @endforeach
        </div>
    </div>
    <div>
        <label class="label">Biografía</label>
        <textarea class="input" name="biografia" rows="5">{{ old('biografia', $musico?->biografia) }}</textarea>
    </div>
    <div class="flex items-center gap-3">
        <button class="btn btn-primary">Guardar perfil</button>
        @if ($musico?->verificado)<span class="badge bg-green-100 text-green-700">✓ Perfil verificado</span>@elseif($musico)<span class="text-xs text-slate-500">Un administrador revisará tu perfil para otorgarte la insignia de verificado.</span>@endif
    </div>
</form>
@endsection
