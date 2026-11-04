<?php

namespace App\Http\Requests;

use App\Support\Ubicacion;
use Illuminate\Foundation\Http\FormRequest;

class SolicitarContratacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esCliente() ?? false;
    }

    public function rules(): array
    {
        return [
            'tipo_evento_id' => ['required', 'exists:tipos_evento,id'],
            ...Ubicacion::reglas($this),
            'fecha_evento' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'horas' => ['required', 'numeric', 'min:0.5', 'max:12'],
            'direccion' => ['required', 'string', 'max:255'],
            'canciones' => ['nullable', 'array'],
            'canciones.*' => ['integer', 'exists:canciones,id'],
        ];
    }

    public function messages(): array
    {
        return Ubicacion::mensajes();
    }

    public function attributes(): array
    {
        return [
            'tipo_evento_id' => 'tipo de evento',
            'ciudad_id' => 'ciudad',
            'fecha_evento' => 'fecha',
            'hora_inicio' => 'hora de inicio',
            'direccion' => 'dirección',
        ];
    }
}
