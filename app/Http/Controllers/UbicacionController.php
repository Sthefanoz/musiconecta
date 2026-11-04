<?php

namespace App\Http\Controllers;

use App\Models\Provincia;
use Illuminate\Http\JsonResponse;

/** Alimenta el dropdown dependiente: al elegir la provincia se cargan sus ciudades. */
class UbicacionController extends Controller
{
    public function ciudades(Provincia $provincia): JsonResponse
    {
        return response()->json(
            $provincia->ciudades()->get(['id', 'nombre'])
        );
    }
}
