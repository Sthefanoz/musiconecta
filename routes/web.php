<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusquedaController;
use App\Http\Controllers\ContratacionController;
use App\Http\Controllers\Musico;
use App\Http\Controllers\PerfilPublicoController;
use App\Http\Controllers\UbicacionController;
use Illuminate\Support\Facades\Route;

/* ---------- Público (sin iniciar sesión) ---------- */
Route::get('/', [BusquedaController::class, 'index'])->name('home');
Route::get('/musicos/{musico}', [PerfilPublicoController::class, 'musico'])->name('musicos.show');
Route::get('/bandas/{banda}', [PerfilPublicoController::class, 'banda'])->name('bandas.show');
Route::get('/ubicacion/provincias/{provincia}/ciudades', [UbicacionController::class, 'ciudades'])->name('ubicacion.ciudades');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/registro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registro', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/* ---------- Usuarios autenticados ---------- */
Route::middleware(['auth', 'perfil'])->group(function () {
    Route::get('/contrataciones/{contratacion}', [ContratacionController::class, 'show'])->name('contrataciones.show');

    /* Cliente */
    Route::middleware('rol:cliente')->group(function () {
        Route::get('/servicios/{servicio}/contratar', [ContratacionController::class, 'create'])->name('contrataciones.create');
        Route::get('/servicios/{servicio}/disponibilidad', [ContratacionController::class, 'disponibilidad'])->name('contrataciones.disponibilidad');
        Route::post('/servicios/{servicio}/contratar', [ContratacionController::class, 'store'])->name('contrataciones.store');
        Route::get('/mis-contrataciones', [ContratacionController::class, 'index'])->name('cliente.contrataciones');
        Route::post('/contrataciones/{contratacion}/anticipo', [ContratacionController::class, 'pagarAnticipo'])->name('contrataciones.anticipo');
        Route::post('/contrataciones/{contratacion}/saldo', [ContratacionController::class, 'pagarSaldo'])->name('contrataciones.saldo');
        Route::post('/contrataciones/{contratacion}/cancelar', [ContratacionController::class, 'cancelar'])->name('contrataciones.cancelar');
        Route::post('/contrataciones/{contratacion}/resena', [ContratacionController::class, 'resena'])->name('contrataciones.resena');
    });
});

/* Músico */
Route::middleware(['auth', 'rol:musico'])->prefix('musico')->name('musico.')->group(function () {
    Route::get('/perfil', [Musico\PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [Musico\PerfilController::class, 'update'])->name('perfil.update');

    Route::middleware('perfil')->group(function () {
        Route::get('/panel', [Musico\PanelController::class, 'index'])->name('panel');

        Route::get('/servicios', [Musico\ServicioController::class, 'index'])->name('servicios.index');
        Route::get('/servicios/crear', [Musico\ServicioController::class, 'create'])->name('servicios.create');
        Route::post('/servicios', [Musico\ServicioController::class, 'store'])->name('servicios.store');
        Route::get('/servicios/{servicio}/editar', [Musico\ServicioController::class, 'edit'])->name('servicios.edit');
        Route::put('/servicios/{servicio}', [Musico\ServicioController::class, 'update'])->name('servicios.update');
        Route::patch('/servicios/{servicio}/activo', [Musico\ServicioController::class, 'toggle'])->name('servicios.toggle');

        Route::get('/repertorio', [Musico\RepertorioController::class, 'index'])->name('repertorio.index');
        Route::post('/repertorio', [Musico\RepertorioController::class, 'store'])->name('repertorio.store');
        Route::delete('/repertorio/{cancion}', [Musico\RepertorioController::class, 'destroy'])->name('repertorio.destroy');

        Route::get('/bandas', [Musico\BandaController::class, 'index'])->name('bandas.index');
        Route::get('/bandas/crear', [Musico\BandaController::class, 'create'])->name('bandas.create');
        Route::post('/bandas', [Musico\BandaController::class, 'store'])->name('bandas.store');
        Route::get('/bandas/{banda}', [Musico\BandaController::class, 'edit'])->name('bandas.edit');
        Route::put('/bandas/{banda}', [Musico\BandaController::class, 'update'])->name('bandas.update');
        Route::post('/bandas/{banda}/integrantes', [Musico\BandaController::class, 'agregarIntegrante'])->name('bandas.integrantes.store');
        Route::put('/bandas/{banda}/integrantes', [Musico\BandaController::class, 'actualizarIntegrantes'])->name('bandas.integrantes.update');
        Route::delete('/bandas/{banda}/integrantes/{musico}', [Musico\BandaController::class, 'quitarIntegrante'])->name('bandas.integrantes.destroy');
        Route::post('/bandas/{banda}/repertorio', [Musico\BandaController::class, 'agregarCancion'])->name('bandas.repertorio.store');
        Route::delete('/bandas/{banda}/repertorio/{cancion}', [Musico\BandaController::class, 'quitarCancion'])->name('bandas.repertorio.destroy');

        Route::get('/agenda', [Musico\AgendaController::class, 'index'])->name('agenda.index');
        Route::post('/agenda', [Musico\AgendaController::class, 'store'])->name('agenda.store');
        Route::delete('/agenda/{bloqueo}', [Musico\AgendaController::class, 'destroy'])->name('agenda.destroy');

        Route::get('/solicitudes', [Musico\SolicitudController::class, 'index'])->name('solicitudes.index');
        Route::post('/solicitudes/{contratacion}/aceptar', [Musico\SolicitudController::class, 'aceptar'])->name('solicitudes.aceptar');
        Route::post('/solicitudes/{contratacion}/rechazar', [Musico\SolicitudController::class, 'rechazar'])->name('solicitudes.rechazar');
        Route::post('/solicitudes/{contratacion}/realizada', [Musico\SolicitudController::class, 'realizada'])->name('solicitudes.realizada');
    });
});

/* Administrador */
Route::middleware(['auth', 'rol:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/catalogos/{catalogo}', [Admin\CatalogoController::class, 'index'])->name('catalogos.index');
    Route::post('/catalogos/{catalogo}', [Admin\CatalogoController::class, 'store'])->name('catalogos.store');
    Route::put('/catalogos/{catalogo}/{id}', [Admin\CatalogoController::class, 'update'])->name('catalogos.update');
    Route::delete('/catalogos/{catalogo}/{id}', [Admin\CatalogoController::class, 'destroy'])->name('catalogos.destroy');
    Route::get('/musicos', [Admin\MusicoController::class, 'index'])->name('musicos.index');
    Route::get('/musicos/crear', [Admin\MusicoController::class, 'create'])->name('musicos.create');
    Route::post('/musicos', [Admin\MusicoController::class, 'store'])->name('musicos.store');
    Route::get('/musicos/{musico}/editar', [Admin\MusicoController::class, 'edit'])->name('musicos.edit');
    Route::put('/musicos/{musico}', [Admin\MusicoController::class, 'update'])->name('musicos.update');
    Route::patch('/musicos/{musico}/verificar', [Admin\MusicoController::class, 'verificar'])->name('musicos.verificar');

    Route::get('/ciudades', [Admin\CiudadController::class, 'index'])->name('ciudades.index');
    Route::post('/ciudades', [Admin\CiudadController::class, 'store'])->name('ciudades.store');
    Route::put('/ciudades/{ciudad}', [Admin\CiudadController::class, 'update'])->name('ciudades.update');
    Route::delete('/ciudades/{ciudad}', [Admin\CiudadController::class, 'destroy'])->name('ciudades.destroy');

    Route::get('/canciones', [Admin\CancionController::class, 'index'])->name('canciones.index');
    Route::post('/canciones', [Admin\CancionController::class, 'store'])->name('canciones.store');
    Route::put('/canciones/{cancion}', [Admin\CancionController::class, 'update'])->name('canciones.update');
    Route::delete('/canciones/{cancion}', [Admin\CancionController::class, 'destroy'])->name('canciones.destroy');
});
