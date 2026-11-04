<?php

namespace App\Services;

use App\Enums\EstadoContratacion;
use App\Models\Musico;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Comparativas del Core. Todas se calculan sobre las contrataciones REALIZADAS dentro del rango
 * (se excluyen solicitadas, aceptadas, confirmadas, rechazadas y canceladas) y sobre la parte del
 * músico, es decir, el monto_neto de su registro de reparto (IN_k para un solista, IN_k·q_i/100 en banda).
 */
class ReporteMusicoService
{
    private Musico $musico;

    private Carbon $desde;

    private Carbon $hasta;

    private ?Collection $filas = null;

    public function para(Musico $musico, Carbon $desde, Carbon $hasta): static
    {
        $clon = clone $this;
        $clon->musico = $musico;
        $clon->desde = $desde->copy()->startOfDay();
        $clon->hasta = $hasta->copy()->endOfDay();
        $clon->filas = null;

        return $clon;
    }

    /** Meses del rango en formato Y-m. */
    public function meses(): array
    {
        return collect(CarbonPeriod::create($this->desde->copy()->startOfMonth(), '1 month', $this->hasta->copy()->startOfMonth()))
            ->map(fn (Carbon $m) => $m->format('Y-m'))
            ->all();
    }

    /**
     * (a) Filtrado + (b) reparto: una fila por contratación realizada del músico con su parte.
     */
    public function filas(): Collection
    {
        return $this->filas ??= $this->consultaBase()
            ->where('r.musico_id', $this->musico->id)
            ->select([
                'c.id', 'c.fecha_evento', 'c.horas', 'c.monto_total', 'c.comision',
                'r.monto_neto', 'r.porcentaje', 's.banda_id',
                'ts.nombre as servicio', 'te.nombre as tipo_evento', 'b.nombre as banda',
            ])
            ->orderBy('c.fecha_evento')
            ->get()
            ->map(function ($f) {
                $f->monto_neto = (float) $f->monto_neto;
                $f->horas = (float) $f->horas;
                $f->porcentaje = (float) $f->porcentaje;
                $f->mes = substr($f->fecha_evento, 0, 7);

                return $f;
            });
    }

    private function consultaBase()
    {
        return DB::table('repartos as r')
            ->join('contrataciones as c', 'c.id', '=', 'r.contratacion_id')
            ->join('servicios as s', 's.id', '=', 'c.servicio_id')
            ->join('tipos_servicio as ts', 'ts.id', '=', 's.tipo_servicio_id')
            ->join('tipos_evento as te', 'te.id', '=', 'c.tipo_evento_id')
            ->leftJoin('bandas as b', 'b.id', '=', 's.banda_id')
            ->where('c.estado', EstadoContratacion::Realizada->value)
            ->where('c.fecha_evento', '>=', $this->desde->toDateString())
            ->where('c.fecha_evento', '<', $this->hasta->copy()->addDay()->toDateString());
    }

    /** Indicadores del encabezado (mes = último mes del rango). */
    public function indicadores(): array
    {
        $meses = $this->meses();
        $actual = end($meses);
        $anterior = Carbon::createFromFormat('Y-m-d', $actual.'-01')->subMonth()->format('Y-m');

        $filas = $this->filas();
        $iActual = $filas->where('mes', $actual)->sum('monto_neto');
        // El mes anterior puede caer fuera del rango, por eso se consulta aparte.
        $iAnterior = (float) DB::table('repartos as r')
            ->join('contrataciones as c', 'c.id', '=', 'r.contratacion_id')
            ->where('r.musico_id', $this->musico->id)
            ->where('c.estado', EstadoContratacion::Realizada->value)
            ->where('c.fecha_evento', '>=', $anterior.'-01')
            ->where('c.fecha_evento', '<', $actual.'-01')
            ->sum('r.monto_neto');

        return [
            'mes' => $actual,
            'ingreso_mes' => $iActual,
            // Variación = (I_m − I_{m−1}) / I_{m−1} × 100
            'variacion' => $iAnterior > 0 ? ($iActual - $iAnterior) / $iAnterior * 100 : null,
            'contrataciones_mes' => $filas->where('mes', $actual)->count(),
            'calificacion' => $this->musico->calificacionPromedio(),
            'resenas' => $this->musico->cantidadResenas(),
        ];
    }

    /** (c) Ingresos y contrataciones por mes y por tipo de servicio. */
    public function ingresosPorMesYServicio(): array
    {
        $meses = $this->meses();
        $filas = $this->filas();
        $servicios = $filas->pluck('servicio')->unique()->sort()->values();

        $series = $servicios->map(fn ($srv) => [
            'servicio' => $srv,
            'valores' => array_map(fn ($m) => round($filas->where('mes', $m)->where('servicio', $srv)->sum('monto_neto'), 2), $meses),
        ])->all();

        $totalesMes = collect($meses)->mapWithKeys(fn ($m) => [$m => round($filas->where('mes', $m)->sum('monto_neto'), 2)]);
        $total = $filas->sum('monto_neto');

        $porServicio = $filas->groupBy('servicio')->map(fn ($g, $srv) => [
            'servicio' => $srv,
            'cantidad' => $g->count(),
            'ingreso' => $g->sum('monto_neto'),
            'promedio' => $g->avg('monto_neto'),
            'participacion' => $total > 0 ? $g->sum('monto_neto') / $total * 100 : 0,
        ])->sortByDesc('cantidad')->values();

        // Conclusiones
        $conclusion = 'No hay contrataciones realizadas en el rango seleccionado.';
        $conclusionServicios = null;
        if ($filas->isNotEmpty()) {
            $mejorMes = $totalesMes->sortDesc()->keys()->first();
            $top = $porServicio->sortByDesc('ingreso')->first();
            $conclusion = 'El mejor mes fue <b>'.mes_corto($mejorMes).'</b> con '.dinero($totalesMes[$mejorMes]).'. '
                .'El servicio que más ingresos genera es <b>'.e($top['servicio']).'</b> ('.pct($top['participacion'], 0).' del total).';

            $masFrecuente = $porServicio->first();
            $mejorPromedio = $porServicio->sortByDesc('promedio')->first();
            $conclusionServicios = $masFrecuente['servicio'] === $mejorPromedio['servicio']
                ? '<b>'.e($masFrecuente['servicio']).'</b> es lo que más te contratan ('.$masFrecuente['cantidad'].' veces) y también lo que más deja por evento ('.dinero($masFrecuente['promedio']).').'
                : '<b>'.e($masFrecuente['servicio']).'</b> es lo que más te contratan ('.$masFrecuente['cantidad'].' veces), pero deja '
                    .dinero($masFrecuente['promedio']).' por evento frente a '.dinero($mejorPromedio['promedio']).' de <b>'.e(mb_strtolower($mejorPromedio['servicio'])).'</b>.';
        }

        return compact('meses', 'series', 'totalesMes', 'porServicio', 'conclusion', 'conclusionServicios');
    }

    /**
     * (d) Solista vs. banda, por forma de trabajo f:
     *   total_f = Σ IN_k (parte del músico), n_f, P_f = total_f / n_f, H_f = total_f / Σ horas_k
     */
    public function solistaVsBanda(): array
    {
        $meses = $this->meses();
        $filas = $this->filas();

        $bandas = $this->musico->bandas()->withCount('integrantes')->get()->keyBy('id');

        $formas = $filas->groupBy(fn ($f) => $f->banda_id ? 'banda:'.$f->banda_id : 'solista')
            ->map(function (Collection $g, string $clave) use ($meses, $bandas) {
                $esSolista = $clave === 'solista';
                $banda = $esSolista ? null : $bandas->get($g->first()->banda_id);
                $total = $g->sum('monto_neto');
                $horas = $g->sum('horas');
                $servicios = $g->pluck('servicio')->unique()->values();

                return [
                    'clave' => $clave,
                    'nombre' => $esSolista ? 'Como solista' : 'Con '.$g->first()->banda,
                    'detalle' => $esSolista
                        ? $servicios->map(fn ($s) => mb_strtolower($s))->join(', ', ' y ')
                        : ($banda ? $banda->integrantes_count.' integrantes · ' : '').'tu parte '.pct($banda?->pivot->porcentaje_pago ?? $g->last()->porcentaje, 0),
                    'total' => $total,
                    'n' => $g->count(),
                    'promedio' => $g->count() ? $total / $g->count() : 0,
                    'por_hora' => $horas > 0 ? $total / $horas : 0,
                    'porcentaje' => $esSolista ? 100 : (float) $g->last()->porcentaje,
                    'valores' => array_map(fn ($m) => round($g->where('mes', $m)->sum('monto_neto'), 2), $meses),
                ];
            })
            ->sortBy(fn ($f) => $f['clave'] === 'solista' ? '0' : '1'.$f['nombre'])
            ->values();

        $conclusion = 'No hay contrataciones realizadas en el rango seleccionado.';
        if ($formas->count() === 1) {
            $f = $formas->first();
            $conclusion = 'En este período solo trabajaste <b>'.e(lcfirst($f['nombre'])).'</b>: '.$f['n'].' contrataciones, '.dinero($f['por_hora']).' por hora.';
        } elseif ($formas->count() > 1) {
            $masVeces = $formas->sortByDesc('n')->first();
            $mejorHora = $formas->sortByDesc('por_hora')->first();
            if ($masVeces['clave'] === $mejorHora['clave']) {
                $conclusion = '<b>'.e($masVeces['nombre']).'</b> es donde más trabajas ('.$masVeces['n'].' contrataciones) y también donde más ganas por hora ('.dinero($mejorHora['por_hora']).').';
            } else {
                $veces = $masVeces['por_hora'] > 0 ? $mejorHora['por_hora'] / $masVeces['por_hora'] : 0;
                $conclusion = e($masVeces['nombre']).' trabajas más veces ('.$masVeces['n'].' contrataciones), pero <b>'.e(lcfirst($mejorHora['nombre'])).'</b> ganas '
                    .dinero($mejorHora['por_hora']).' por hora, '.number_format($veces, 1, ',', '.').' veces más'
                    .($mejorHora['porcentaje'] < 100 ? ', aunque solo te toque el '.pct($mejorHora['porcentaje'], 0) : '').'.';
            }
        }

        return compact('meses', 'formas', 'conclusion');
    }

    /**
     * (e) Repertorio solicitado: el ingreso de la contratación k se reparte entre los estilos de las
     * canciones pedidas en proporción s_{k,e} / s_k. Ranking de canciones y faltantes en el repertorio.
     */
    public function repertorioSolicitado(): array
    {
        $filas = $this->filas()->keyBy('id');

        $pedidas = DB::table('contratacion_cancion as cc')
            ->join('canciones as ca', 'ca.id', '=', 'cc.cancion_id')
            ->join('generos as g', 'g.id', '=', 'ca.genero_id')
            ->whereIn('cc.contratacion_id', $filas->keys())
            ->select('cc.contratacion_id', 'ca.id', 'ca.titulo', 'g.nombre as estilo')
            ->get();

        $ingresoEstilo = [];
        foreach ($pedidas->groupBy('contratacion_id') as $contratacionId => $canciones) {
            $sk = $canciones->count();
            $monto = $filas[$contratacionId]->monto_neto;
            foreach ($canciones->groupBy('estilo') as $estilo => $deEstilo) {
                $ingresoEstilo[$estilo] = ($ingresoEstilo[$estilo] ?? 0) + $monto * $deEstilo->count() / $sk;
            }
        }
        arsort($ingresoEstilo);

        $miRepertorio = $this->musico->repertorio()->pluck('canciones.id')->flip();
        $ranking = $pedidas->groupBy('id')->map(fn ($g) => [
            'id' => $g->first()->id,
            'titulo' => $g->first()->titulo,
            'estilo' => $g->first()->estilo,
            'pedidos' => $g->count(),
            'en_repertorio' => $miRepertorio->has($g->first()->id),
        ])->sortByDesc('pedidos')->values();

        // Las faltantes se buscan en todas las canciones pedidas, no solo en el top 10.
        $faltantes = $ranking->where('en_repertorio', false)->values();
        $ranking = $ranking->take(10)->merge($faltantes->take(1))->unique('id')->values();

        $conclusion = 'Tus clientes todavía no han pedido canciones en contrataciones realizadas de este período.';
        if ($ranking->isNotEmpty()) {
            $totalIngreso = $this->filas()->sum('monto_neto');
            $topEstilo = array_key_first($ingresoEstilo);
            $conclusion = 'El <b>'.e(mb_strtolower($topEstilo)).'</b> genera el '.pct($totalIngreso > 0 ? $ingresoEstilo[$topEstilo] / $totalIngreso * 100 : 0, 0)
                .' de tus ingresos y "'.e($ranking->first()['titulo']).'" es tu canción más pedida.';
            if ($faltantes->isNotEmpty()) {
                $f = $faltantes->first();
                $conclusion .= ' <b>"'.e($f['titulo']).'"</b> te la pidieron '.$f['pedidos'].' '.($f['pedidos'] === 1 ? 'vez' : 'veces')
                    .' y no está en tu repertorio: agregarla podría sumar contrataciones.';
            } else {
                $conclusion .= ' Todas las canciones más pedidas ya están en tu repertorio.';
            }
        }

        return [
            'estilos' => collect($ingresoEstilo)->map(fn ($v) => round($v, 2)),
            'ranking' => $ranking,
            'faltantes' => $faltantes,
            'conclusion' => $conclusion,
        ];
    }

    /** (f) Ingresos por tipo de evento: cantidad, ingreso, promedio y participación en el total. */
    public function ingresosPorTipoEvento(): array
    {
        $filas = $this->filas();
        $total = $filas->sum('monto_neto');

        $tipos = $filas->groupBy('tipo_evento')->map(fn ($g, $tipo) => [
            'tipo' => $tipo,
            'cantidad' => $g->count(),
            'ingreso' => $g->sum('monto_neto'),
            'promedio' => $g->avg('monto_neto'),
            'participacion' => $total > 0 ? $g->sum('monto_neto') / $total * 100 : 0,
        ])->sortByDesc('ingreso')->values();

        $conclusion = 'No hay contrataciones realizadas en el rango seleccionado.';
        if ($tipos->isNotEmpty()) {
            $mejor = $tipos->sortByDesc('promedio')->first();
            $frecuente = $tipos->sortByDesc('cantidad')->first();
            $conclusion = 'Los eventos de tipo <b>'.e(mb_strtolower($mejor['tipo'])).'</b> dejan el mayor ingreso por contratación ('.dinero($mejor['promedio']).')';
            $conclusion .= $frecuente['tipo'] !== $mejor['tipo']
                ? ', mientras que <b>'.e(mb_strtolower($frecuente['tipo'])).'</b> es el más frecuente ('.$frecuente['cantidad'].' contrataciones, '.dinero($frecuente['promedio']).' en promedio).'
                : ' y además son los más frecuentes.';
        }

        return compact('tipos', 'total', 'conclusion');
    }

    /**
     * (g) Comparación con los N músicos del mismo género principal y ciudad:
     *   Ī_g = (1/N) Σ I_j,  Diferencia = (I_m − Ī_g) / Ī_g × 100
     *   Posición = % de músicos del grupo que ganaron menos que él;  meta = percentil 75 del grupo.
     */
    public function comparacionConSuGenero(): array
    {
        $meses = $this->meses();
        $paresIds = Musico::where('genero_principal_id', $this->musico->genero_principal_id)
            ->where('ciudad_id', $this->musico->ciudad_id)
            ->pluck('id');
        $n = $paresIds->count();

        $porMes = $this->consultaBase()
            ->whereIn('r.musico_id', $paresIds)
            ->selectRaw('r.musico_id, substr(c.fecha_evento, 1, 7) as mes, sum(r.monto_neto) as total')
            ->groupBy('r.musico_id', 'mes')
            ->get();

        $totales = $paresIds->mapWithKeys(fn ($id) => [$id => (float) $porMes->where('musico_id', $id)->sum('total')]);
        $yo = $totales[$this->musico->id] ?? 0.0;
        $promedio = $n > 0 ? $totales->avg() : 0;
        $diferencia = $promedio > 0 ? ($yo - $promedio) / $promedio * 100 : null;

        $otros = $totales->except($this->musico->id);
        $menosQueYo = $otros->filter(fn ($v) => $v < $yo)->count();
        $posicion = $otros->count() > 0 ? $menosQueYo / $otros->count() * 100 : null;
        $p75 = self::percentil($totales->values()->all(), 75);

        $serie = ['yo' => [], 'promedio' => [], 'p75' => []];
        foreach ($meses as $mes) {
            $valores = $paresIds->map(fn ($id) => (float) $porMes->where('musico_id', $id)->where('mes', $mes)->sum('total'))->all();
            $serie['yo'][] = round((float) $porMes->where('musico_id', $this->musico->id)->where('mes', $mes)->sum('total'), 2);
            $serie['promedio'][] = round($n > 0 ? array_sum($valores) / $n : 0, 2);
            $serie['p75'][] = round(self::percentil($valores, 75), 2);
        }

        $genero = $this->musico->generoPrincipal->nombre;
        $ciudad = $this->musico->ciudad->nombre;
        $faltaMensual = max(0, $p75 - $yo) / max(1, count($meses));

        if ($n <= 1) {
            $conclusion = 'Todavía no hay otros músicos de '.e(mb_strtolower($genero)).' en '.e($ciudad).' para comparar.';
        } elseif ($diferencia === null) {
            $conclusion = 'Nadie de tu grupo registra ingresos en este período.';
        } else {
            $conclusion = 'En el período ganaste un <b>'.pct(abs($diferencia), 0).' '.($diferencia >= 0 ? 'más' : 'menos').'</b> que el promedio de los músicos de '
                .e(mb_strtolower($genero)).' en '.e($ciudad).'. ';
            $conclusion .= $yo >= $p75
                ? 'Ya estás en el 25 % superior de tu género.'
                : 'Para entrar al 25 % superior te faltan unos '.dinero($faltaMensual, 0).' al mes.';
        }

        return compact('meses', 'n', 'yo', 'promedio', 'diferencia', 'posicion', 'menosQueYo', 'p75', 'serie', 'genero', 'ciudad', 'conclusion');
    }

    /** Percentil por interpolación lineal. */
    public static function percentil(array $valores, float $p): float
    {
        if ($valores === []) {
            return 0.0;
        }
        sort($valores);
        $pos = (count($valores) - 1) * $p / 100;
        $inf = (int) floor($pos);
        $sup = (int) ceil($pos);

        return $valores[$inf] + ($valores[$sup] - $valores[$inf]) * ($pos - $inf);
    }
}
