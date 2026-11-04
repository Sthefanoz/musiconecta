@extends('layouts.app')
@section('title', 'Registro')

@section('content')
<div class="mx-auto max-w-md">
    <div class="card">
        <h1 class="text-2xl font-bold">Crear cuenta</h1>
        <p class="subtitulo">Regístrate como cliente para contratar, o como músico para ofrecer tus servicios.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                @foreach (['cliente' => 'Soy cliente', 'musico' => 'Soy músico'] as $valor => $texto)
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 p-3 text-sm has-checked:border-marca-600 has-checked:bg-marca-50">
                        <input type="radio" name="rol" value="{{ $valor }}" @checked(old('rol', $rol) === $valor)> {{ $texto }}
                    </label>
                @endforeach
            </div>
            <div>
                <label class="label" for="name">Nombre completo</label>
                <input class="input" name="name" id="name" value="{{ old('name') }}" required>
            </div>
            <div>
                <label class="label" for="email">Correo</label>
                <input class="input" type="email" name="email" id="email" value="{{ old('email') }}" required>
            </div>
            <div>
                <label class="label" for="telefono">Teléfono</label>
                <input class="input" name="telefono" id="telefono" value="{{ old('telefono') }}">
            </div>
            <div>
                <label class="label" for="password">Contraseña</label>
                <input class="input" type="password" name="password" id="password" required>
            </div>
            <div>
                <label class="label" for="password_confirmation">Confirmar contraseña</label>
                <input class="input" type="password" name="password_confirmation" id="password_confirmation" required>
            </div>
            <button class="btn btn-primary w-full">Crear cuenta</button>
        </form>
    </div>
</div>
@endsection
