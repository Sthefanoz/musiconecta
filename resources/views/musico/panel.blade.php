@extends('layouts.app')
@section('title', 'Mi panel')

@php
    $etiquetas = array_map('mes_corto', $mesServicio['meses']);

    $cfgMesServicio = [
        'type' => 'bar',
        'data' => ['labels' => $etiquetas, 'datasets' => array_map(fn ($s) => ['label' => $s['servicio'], 'data' => $s['valores']], $mesServicio['series'])],
        'options' => ['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'beginAtZero' => true]]],
    ];
    $cfgConteoServicio = [
        'type' => 'bar',
        'data' => [
            'labels' => $mesServicio['porServicio']->pluck('servicio')->all(),
            'datasets' => [['label' => 'Contrataciones', 'data' => $mesServicio['porServicio']->pluck('cantidad')->all(),
                'backgroundColor' => array_slice(['#5b4fd8', '#0ea5e9', '#16a34a', '#f59e0b', '#db2777', '#64748b'], 0, max(1, $mesServicio['porServicio']->count()))]],
        ],
        'options' => ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]],
    ];
    $cfgFormas = [
        'type' => 'bar',
        'data' => ['labels' => $etiquetas, 'datasets' => $formas['formas']->map(fn ($f) => ['label' => $f['nombre'], 'data' => $f['valores']])->all()],
        'options' => ['scales' => ['y' => ['beginAtZero' => true]]],
    ];
    $cfgEstilos = [
        'type' => 'bar',
        'data' => ['labels' => $repertorio['estilos']->keys()->all(), 'datasets' => [['label' => 'Ingreso atribuido', 'data' => $repertorio['estilos']->values()->all()]]],
        'options' => ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['beginAtZero' => true]]],
    ];
    $cfgEventos = [
        'type' => 'doughnut',
        'data' => ['labels' => $eventos['tipos']->pluck('tipo')->all(), 'datasets' => [['label' => 'Ingresos', 'data' => $eventos['tipos']->pluck('ingreso')->map(fn ($v) => round($v, 2))->all()]]],
        'options' => ['cutout' => '50%', 'plugins' => ['legend' => ['position' => 'right']]],
    ];
    $cfgGenero = [
        'type' => 'line',
        'data' => ['labels' => $etiquetas, 'datasets' => [
            ['label' => 'Tú', 'data' => $genero['serie']['yo'], 'borderColor' => '#5b4fd8', 'backgroundColor' => '#5b4fd8', 'tension' => 0.35, 'borderWidth' => 3],
            ['label' => 'Promedio del género', 'data' => $genero['serie']['promedio'], 'borderColor' => '#94a3b8', 'backgroundColor' => '#94a3b8', 'borderDash' => [6, 4], 'pointRadius' => 0, 'tension' => 0.35],
            ['label' => 'Percentil 75', 'data' => $genero['serie']['p75'], 'borderColor' => '#f59e0b', 'backgroundColor' => '#f59e0b', 'borderDash' => [2, 3], 'pointRadius' => 0, 'tension' => 0.35],
        ]],
        'options' => ['scales' => ['y' => ['beginAtZero' => true]]],
    ];
@endphp

@section('content')
<h1 class="titulo">Mi panel</h1>
<p class="subtitulo">Comparativas de tu trabajo como músico · {{ $musico->generoPrincipal->nombre }} · {{ $musico->ciudad->nombre }}</p>

<nav class="mt-6 flex flex-wrap gap-8 border-b border-slate-200">
    <a href="#ingresos" class="tab tab-activa">Ingresos</a>
    <a href="#formas" class="tab">Solista vs. banda</a>
    <a href="#repertorio" class="tab">Repertorio</a>
    <a href="#eventos" class="tab">Tipo de evento</a>
    <a href="#genero" class="tab">Mi género</a>
</nav>

<form method="GET" class="mt-6 flex flex-wrap items-end gap-4">
    <div><label class="label">Desde</label><input class="input" type="date" name="desde" value="{{ $desde->toDateString() }}"></div>
    <div><label class="label">Hasta</label><input class="input" type="date" name="hasta" value="{{ $hasta->toDateString() }}"></div>
    <button class="btn btn-primary">Aplicar</button>
    <a href="{{ route('musico.panel') }}" class="btn btn-light">Últimos 12 meses</a>
</form>

{{-- Indicadores --}}
<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="card">
        <p class="text-sm text-slate-500">Ingreso neto · {{ mes_corto($kpi['mes']) }}</p>
        <p class="mt-1 text-3xl font-bold">{{ dinero($kpi['ingreso_mes']) }}</p>
        @if ($kpi['variacion'] !== null)
            <p class="mt-1 text-sm {{ $kpi['variacion'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $kpi['variacion'] >= 0 ? '▲' : '▼' }} {{ pct(abs($kpi['variacion'])) }} vs mes anterior</p>
        @else
            <p class="mt-1 text-sm text-slate-400">Sin ingresos el mes anterior</p>
        @endif
    </div>
    <div class="card">
        <p class="text-sm text-slate-500">Contrataciones · {{ mes_corto($kpi['mes']) }}</p>
        <p class="mt-1 text-3xl font-bold">{{ $kpi['contrataciones_mes'] }}</p>
        <p class="mt-1 text-sm text-slate-500">realizadas</p>
    </div>
    <div class="card">
        <p class="text-sm text-slate-500">Calificación promedio</p>
        <p class="mt-1 text-3xl font-bold">{{ $kpi['calificacion'] ? number_format($kpi['calificacion'], 1, ',', '.').' ★' : '—' }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ $kpi['resenas'] }} reseñas</p>
    </div>
    <div class="card">
        <p class="text-sm text-slate-500">Posición en tu género</p>
        <p class="mt-1 text-3xl font-bold">{{ $genero['posicion'] !== null ? 'Percentil '.round($genero['posicion']) : '—' }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ $genero['genero'] }} · {{ $genero['ciudad'] }}</p>
    </div>
</div>

{{-- 1 · Ingresos por mes y servicio --}}
<section id="ingresos" class="mt-6 grid scroll-mt-6 gap-6 lg:grid-cols-5">
    <div class="card lg:col-span-3">
        <h2 class="text-xl font-bold">1 · Ingresos por mes y servicio</h2>
        <div class="mt-4 h-80"><canvas data-chart="{{ json_encode($cfgMesServicio) }}" data-money="y"></canvas></div>
        <div class="conclusion">{!! $mesServicio['conclusion'] !!}</div>
    </div>
    <div class="card lg:col-span-2">
        <h2 class="text-xl font-bold">Contrataciones por servicio</h2>
        <div class="mt-4 h-80"><canvas data-chart="{{ json_encode($cfgConteoServicio) }}"></canvas></div>
        @if ($mesServicio['conclusionServicios'])<div class="conclusion">{!! $mesServicio['conclusionServicios'] !!}</div>@endif
    </div>
</section>

{{-- 2 · Solista vs. banda --}}
<section id="formas" class="card mt-6 scroll-mt-6">
    <h2 class="text-xl font-bold">2 · Solista vs. banda</h2>
    <p class="subtitulo">Lo que ganaste en cada forma de trabajo (tu parte, después de la comisión de la plataforma).</p>
    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($formas['formas'] as $f)
            <div class="rounded-xl border border-slate-200 p-5">
                <p class="text-sm text-slate-500">{{ $f['nombre'] }} · {{ $f['detalle'] }}</p>
                <p class="mt-1 text-3xl font-bold">{{ dinero($f['total']) }}</p>
                <p class="mt-1 text-sm">{{ $f['n'] }} contrataciones · {{ dinero($f['promedio']) }} por contratación · {{ dinero($f['por_hora']) }} por hora</p>
            </div>
        @endforeach
    </div>
    @if ($formas['formas']->isNotEmpty())
        <div class="mt-6 h-80"><canvas data-chart="{{ json_encode($cfgFormas) }}" data-money="y"></canvas></div>
    @endif
    <div class="conclusion">{!! $formas['conclusion'] !!}</div>
</section>

{{-- 3 · Repertorio solicitado --}}
<section id="repertorio" class="card mt-6 scroll-mt-6">
    <h2 class="text-xl font-bold">3 · Repertorio solicitado</h2>
    <div class="mt-4 grid gap-6 lg:grid-cols-2">
        <div>
            <p class="text-slate-500">Ingreso atribuido a cada estilo según las canciones que pidieron los clientes.</p>
            <div class="mt-4 h-72"><canvas data-chart="{{ json_encode($cfgEstilos) }}" data-money="x"></canvas></div>
        </div>
        <div>
            <p class="text-slate-500">Canciones más solicitadas</p>
            <table class="tabla mt-2">
                <thead><tr><th>Canción</th><th>Estilo</th><th class="text-right">Pedidos</th><th>En tu repertorio</th></tr></thead>
                <tbody>
                @forelse ($repertorio['ranking'] as $c)
                    <tr>
                        <td>{{ $c['titulo'] }}</td>
                        <td>{{ $c['estilo'] }}</td>
                        <td class="text-right">{{ $c['pedidos'] }}</td>
                        <td><span class="badge {{ $c['en_repertorio'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $c['en_repertorio'] ? 'Sí' : 'No' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-6 text-center text-slate-500">Sin datos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="conclusion">{!! $repertorio['conclusion'] !!}</div>
</section>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    {{-- 4 · Ingresos por tipo de evento --}}
    <section id="eventos" class="card scroll-mt-6">
        <h2 class="text-xl font-bold">4 · Ingresos por tipo de evento</h2>
        @if ($eventos['tipos']->isNotEmpty())
            <div class="mt-4 h-64"><canvas data-chart="{{ json_encode($cfgEventos) }}" data-money="value"></canvas></div>
        @endif
        <table class="tabla mt-4">
            <thead><tr><th>Tipo de evento</th><th class="text-right">Contrataciones</th><th class="text-right">Ingresos</th><th class="text-right">Promedio</th><th class="text-right">Part.</th></tr></thead>
            <tbody>
            @forelse ($eventos['tipos'] as $t)
                <tr>
                    <td>{{ $t['tipo'] }}</td>
                    <td class="text-right">{{ $t['cantidad'] }}</td>
                    <td class="text-right">{{ dinero($t['ingreso']) }}</td>
                    <td class="text-right">{{ dinero($t['promedio']) }}</td>
                    <td class="text-right">{{ pct($t['participacion'], 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-slate-500">Sin datos.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="conclusion">{!! $eventos['conclusion'] !!}</div>
    </section>

    {{-- 5 · Tú vs. tu género --}}
    <section id="genero" class="card scroll-mt-6">
        <h2 class="text-xl font-bold">5 · Tú vs. tu género</h2>
        <p class="subtitulo">Ingreso neto mensual frente al promedio y al percentil 75 de los {{ $genero['n'] }} músicos de {{ mb_strtolower($genero['genero']) }} en {{ $genero['ciudad'] }}.</p>
        <div class="mt-4 h-72"><canvas data-chart="{{ json_encode($cfgGenero) }}" data-money="y"></canvas></div>

        @if ($genero['posicion'] !== null)
            <p class="mt-4 text-sm text-slate-600">Tu posición (percentil {{ round($genero['posicion']) }}): ganas más que {{ $genero['menosQueYo'] }} de {{ $genero['n'] - 1 }} músicos de tu género</p>
            <div class="mt-2 h-2.5 w-full rounded-full bg-slate-200"><div class="h-2.5 rounded-full bg-marca-600" style="width: {{ round($genero['posicion']) }}%"></div></div>
            <dl class="mt-4 grid grid-cols-3 gap-3 text-center text-sm">
                <div class="rounded-lg bg-slate-50 p-2"><dt class="text-slate-500">Tú</dt><dd class="font-bold">{{ dinero($genero['yo']) }}</dd></div>
                <div class="rounded-lg bg-slate-50 p-2"><dt class="text-slate-500">Promedio</dt><dd class="font-bold">{{ dinero($genero['promedio']) }}</dd></div>
                <div class="rounded-lg bg-slate-50 p-2"><dt class="text-slate-500">Percentil 75</dt><dd class="font-bold">{{ dinero($genero['p75']) }}</dd></div>
            </dl>
        @endif
        <div class="conclusion">{!! $genero['conclusion'] !!}</div>
    </section>
</div>
@endsection
