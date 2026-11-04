<?php

namespace App\Providers;

use App\Models\Provincia;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // En producción el sitio se sirve detrás de un proxy HTTPS.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Provincias (con sus ciudades) para el dropdown dependiente.
        View::composer('partials.ubicacion', function ($view) {
            $view->with('provincias', Provincia::with('ciudades')->orderBy('nombre')->get());
        });
    }
}
