@extends('layouts.app')
@section('title', $musico->exists ? 'Editar músico' : 'Nuevo músico')

@section('content')
<h1 class="titulo">{{ $musico->exists ? 'Editar '.$musico->nombre_artistico : 'Nuevo músico' }}</h1>
<p class="subtitulo">Los datos se validan en el servidor antes de guardarse.</p>

@php $seleccionados = old('generos', $musico->exists ? $musico->generos->pluck('id')->all() : []); @endphp
<form method="POST" action="{{ $musico->exists ? route('admin.musicos.update', $musico) : route('admin.musicos.store') }}" class="mt-6 grid max-w-4xl gap-6" novalidate>
    @csrf
    @if ($musico->exists) @method('PUT') @endif

    <div class="card space-y-4">
        <h2 class="text-lg font-bold">Cuenta</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="name">Nombre completo</label>
                <input class="input" id="name" name="name" value="{{ old('name', $musico->user?->name) }}">
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="email">Correo</label>
                <input class="input" id="email" name="email" type="email" value="{{ old('email', $musico->user?->email) }}">
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password">Contraseña {{ $musico->exists ? '(dejar vacío para no cambiarla)' : '' }}</label>
                <input class="input" id="password" name="password" type="password" autocomplete="new-password">
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="telefono">Teléfono</label>
                <input class="input" id="telefono" name="telefono" value="{{ old('telefono', $musico->user?->telefono) }}" placeholder="0991234567">
                @error('telefono')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="card space-y-4">
        <h2 class="text-lg font-bold">Perfil del músico</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="cedula">Cédula <span class="badge bg-red-100 text-red-700">dato sensible</span></label>
                <input class="input font-mono" id="cedula" name="cedula" value="{{ old('cedula', $musico->cedula) }}" maxlength="12" placeholder="1712345678">
                @error('cedula')<p class="error">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-slate-500">Se verifica en el servidor: 10 dígitos, código de provincia, tercer dígito y dígito verificador (módulo 10). No puede repetirse.</p>
            </div>
            <div>
                <label class="label" for="nombre_artistico">Nombre artístico</label>
                <input class="input" id="nombre_artistico" name="nombre_artistico" value="{{ old('nombre_artistico', $musico->nombre_artistico) }}">
                @error('nombre_artistico')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>

        @include('partials.ubicacion', ['ciudadId' => $musico->ciudad_id])

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="genero_principal_id">Género principal</label>
                <select class="input" id="genero_principal_id" name="genero_principal_id">
                    <option value="">Selecciona…</option>
                    @foreach ($generos as $g)<option value="{{ $g->id }}" @selected(old('genero_principal_id', $musico->genero_principal_id) == $g->id)>{{ $g->nombre }}</option>@endforeach
                </select>
                @error('genero_principal_id')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="anios_experiencia">Años de experiencia</label>
                <input class="input" id="anios_experiencia" name="anios_experiencia" type="number" min="0" max="80" value="{{ old('anios_experiencia', $musico->anios_experiencia) }}">
                @error('anios_experiencia')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <p class="label">Otros géneros que interpreta</p>
            <div class="grid gap-2 sm:grid-cols-3">
                @foreach ($generos as $g)
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="generos[]" value="{{ $g->id }}" class="accent-marca-600" @checked(in_array($g->id, $seleccionados))> {{ $g->nombre }}</label>
                @endforeach
            </div>
        </div>
        <div>
            <label class="label" for="biografia">Biografía</label>
            <textarea class="input" id="biografia" name="biografia" rows="4">{{ old('biografia', $musico->biografia) }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="verificado" value="1" class="accent-marca-600" @checked(old('verificado', $musico->verificado))> Perfil verificado (muestra la insignia)</label>
    </div>

    <div class="flex gap-2">
        <button class="btn btn-primary">Guardar</button>
        <a href="{{ route('admin.musicos.index') }}" class="btn btn-light">Cancelar</a>
    </div>
</form>
@endsection
