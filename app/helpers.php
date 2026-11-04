<?php

if (! function_exists('dinero')) {
    /** $1.240 / $299,25 (formato es-EC). */
    function dinero(float|int|string|null $valor, ?int $decimales = null): string
    {
        $valor = (float) $valor;
        $decimales ??= (abs($valor - round($valor)) < 0.005) ? 0 : 2;

        return ($valor < 0 ? '-$' : '$').number_format(abs($valor), $decimales, ',', '.');
    }
}

if (! function_exists('pct')) {
    /** 27,8 % */
    function pct(float|int|null $valor, int $decimales = 1): string
    {
        return number_format((float) $valor, $decimales, ',', '.').' %';
    }
}

if (! function_exists('mes_corto')) {
    /** '2025-11' → 'Nov. 2025' */
    function mes_corto(string $ym): string
    {
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        [$anio, $mes] = explode('-', $ym);

        return $meses[(int) $mes - 1].'. '.$anio;
    }
}
