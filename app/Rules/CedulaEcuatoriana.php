<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida una cédula ecuatoriana en el back-end (no basta con validarla en JavaScript):
 *  - exactamente 10 dígitos numéricos;
 *  - los dos primeros = código de provincia (01–24, o 30 para ecuatorianos registrados en el exterior);
 *  - el tercer dígito < 6 (persona natural);
 *  - el décimo dígito es el verificador del algoritmo módulo 10
 *    (coeficientes 2,1,2,1,2,1,2,1,2; si el producto > 9 se resta 9).
 */
class CedulaEcuatoriana implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::esValida((string) $value)) {
            $fail('La :attribute no es una cédula ecuatoriana válida.');
        }
    }

    public static function esValida(string $cedula): bool
    {
        if (! preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }

        $provincia = (int) substr($cedula, 0, 2);
        if (! (($provincia >= 1 && $provincia <= 24) || $provincia === 30)) {
            return false;
        }

        if ((int) $cedula[2] >= 6) {
            return false;
        }

        return (int) $cedula[9] === self::digitoVerificador(substr($cedula, 0, 9));
    }

    /** Calcula el dígito verificador a partir de los 9 primeros dígitos. */
    public static function digitoVerificador(string $nueve): int
    {
        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $producto = (int) $nueve[$i] * ($i % 2 === 0 ? 2 : 1);
            $suma += $producto > 9 ? $producto - 9 : $producto;
        }

        return (10 - $suma % 10) % 10;
    }
}
