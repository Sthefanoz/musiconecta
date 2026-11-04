<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Reglas para el par Provincia → Ciudad de los formularios.
 * El back-end comprueba que la ciudad elegida pertenezca a la provincia elegida
 * (no se confía en lo que envía el dropdown).
 */
class Ubicacion
{
    public static function reglas(Request $request): array
    {
        return [
            'provincia_id' => ['required', 'integer', 'exists:provincias,id'],
            'ciudad_id' => [
                'required', 'integer',
                Rule::exists('ciudades', 'id')->where('provincia_id', (int) $request->input('provincia_id')),
            ],
        ];
    }

    public static function mensajes(): array
    {
        return ['ciudad_id.exists' => 'La ciudad seleccionada no pertenece a la provincia elegida.'];
    }
}
