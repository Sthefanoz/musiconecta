<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Buscar músicos') · MusiConecta</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
@php
    $user = auth()->user();
    $link = fn (string $ruta, string $patron) => request()->routeIs($patron)
        ? 'text-white border-b-2 border-white pb-0.5'
        : 'text-slate-300 hover:text-white';
@endphp
<nav class="bg-slate-900">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold text-white">
            <span>🎸</span> MusiConecta
        </a>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
            <a href="{{ route('home') }}" class="{{ $link('home', 'home') }}">Buscar músicos</a>
            @auth
                @if ($user->esCliente())
                    <a href="{{ route('cliente.contrataciones') }}" class="{{ $link('', 'cliente.*') }}">Mis contrataciones</a>
                @elseif ($user->esMusico() && $user->musico)
                    <a href="{{ route('musico.solicitudes.index') }}" class="{{ $link('', 'musico.solicitudes.*') }}">Solicitudes</a>
                    <a href="{{ route('musico.agenda.index') }}" class="{{ $link('', 'musico.agenda.*') }}">Agenda</a>
                    <a href="{{ route('musico.panel') }}" class="{{ $link('', 'musico.panel') }}">Mi panel</a>
                    <div class="group relative">
                        <button class="text-slate-300 hover:text-white">Mi perfil ▾</button>
                        <div class="invisible absolute right-0 z-20 w-48 pt-2 group-hover:visible">
                            <div class="rounded-lg bg-white py-1 text-slate-700 shadow-lg ring-1 ring-slate-200">
                                <a class="block px-4 py-2 hover:bg-slate-50" href="{{ route('musico.perfil.edit') }}">Datos del perfil</a>
                                <a class="block px-4 py-2 hover:bg-slate-50" href="{{ route('musico.servicios.index') }}">Servicios</a>
                                <a class="block px-4 py-2 hover:bg-slate-50" href="{{ route('musico.repertorio.index') }}">Repertorio</a>
                                <a class="block px-4 py-2 hover:bg-slate-50" href="{{ route('musico.bandas.index') }}">Bandas</a>
                                <a class="block px-4 py-2 hover:bg-slate-50" href="{{ route('musicos.show', $user->musico) }}">Ver perfil público</a>
                            </div>
                        </div>
                    </div>
                @elseif ($user->esAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="{{ $link('', 'admin.dashboard') }}">Resumen</a>
                    <a href="{{ route('admin.musicos.index') }}" class="{{ $link('', 'admin.musicos.*') }}">Músicos</a>
                    <a href="{{ route('admin.canciones.index') }}" class="{{ $link('', 'admin.canciones.*') }}">Canciones</a>
                    <a href="{{ route('admin.ciudades.index') }}" class="{{ $link('', 'admin.ciudades.*') }}">Ciudades</a>
                    <a href="{{ route('admin.catalogos.index', 'generos') }}" class="{{ $link('', 'admin.catalogos.*') }}">Catálogos</a>
                @endif
                <span class="text-slate-400">{{ $user->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-md border border-slate-500 px-3 py-1 text-white hover:bg-slate-800">Salir</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-slate-300 hover:text-white">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="rounded-md bg-marca-600 px-3 py-1.5 text-white hover:bg-marca-700">Registrarse</a>
            @endauth
        </div>
    </div>
</nav>

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-8">
    @if (session('ok'))
        <div class="mb-6 rounded-lg bg-green-100 px-4 py-3 text-sm text-green-800">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<footer class="py-8 text-center text-xs text-slate-400">MusiConecta · Ingeniería Web · {{ date('Y') }}</footer>
</body>
</html>
