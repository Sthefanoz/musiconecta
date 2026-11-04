@extends('layouts.app')
@section('title', 'Iniciar sesión')

@section('content')
<div class="mx-auto max-w-md">
    <div class="card">
        <h1 class="text-2xl font-bold">Iniciar sesión</h1>
        <p class="subtitulo">Accede para contratar músicos o gestionar tus servicios.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="label" for="email">Correo</label>
                <input class="input" type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div>
                <label class="label" for="password">Contraseña</label>
                <input class="input" type="password" name="password" id="password" required>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300"> Recordarme
            </label>
            <button class="btn btn-primary w-full">Entrar</button>
        </form>
        <p class="mt-4 text-center text-sm text-slate-500">¿No tienes cuenta? <a class="text-marca-600 hover:underline" href="{{ route('register') }}">Regístrate</a></p>
    </div>

    <div class="mt-4 rounded-lg bg-slate-100 p-4 text-xs text-slate-600">
        <p class="font-semibold">Cuentas de prueba (contraseña: <code>password</code>)</p>
        <ul class="mt-1 space-y-0.5">
            <li>Músico: <code>andres@musiconecta.test</code></li>
            <li>Cliente: <code>maria@musiconecta.test</code></li>
            <li>Administrador: <code>admin@musiconecta.test</code></li>
        </ul>
    </div>
</div>
@endsection
