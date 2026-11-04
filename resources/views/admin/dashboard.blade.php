@extends('layouts.app')
@section('title', 'Administración')

@php
    $cfgComision = [
        'type' => 'bar',
        'data' => [
            'labels' => $meses->map(fn ($m) => mes_corto($m))->all(),
            'datasets' => [['label' => 'Comisión', 'data' => $meses->map(fn ($m) => round((float) ($comisionMes[$m]->comision ?? 0), 2))->all()]],
        ],
        'options' => ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true]]],
    ];
@endphp

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="titulo">Resumen de la plataforma</h1>
        <p class="subtitulo">Comisión ganada ({{ config('musiconecta.comision') * 100 }} % de cada contratación realizada) y músicos más contratados.</p>
    </div>
    <form method="GET" class="flex items-end gap-2">
        <div><label class="label">Año</label><input class="input w-28" type="number" name="anio" value="{{ $anio }}"></div>
        <button class="btn btn-primary">Ver</button>
    </form>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="card"><p class="text-sm text-slate-500">Comisión {{ $anio }}</p><p class="mt-1 text-3xl font-bold">{{ dinero($totalComision) }}</p><p class="text-sm text-slate-500">sobre {{ dinero($totalFacturado) }} facturados</p></div>
    <div class="card"><p class="text-sm text-slate-500">Músicos</p><p class="mt-1 text-3xl font-bold">{{ $stats['musicos'] }}</p><p class="text-sm text-slate-500">{{ $stats['verificados'] }} verificados</p></div>
    <div class="card"><p class="text-sm text-slate-500">Clientes</p><p class="mt-1 text-3xl font-bold">{{ $stats['clientes'] }}</p></div>
    <div class="card"><p class="text-sm text-slate-500">Solicitudes sin responder</p><p class="mt-1 text-3xl font-bold">{{ $stats['pendientes'] }}</p></div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-5">
    <div class="card lg:col-span-3">
        <h2 class="text-xl font-bold">Comisión por mes</h2>
        <div class="mt-4 h-72"><canvas data-chart="{{ json_encode($cfgComision) }}" data-money="y"></canvas></div>
        <table class="tabla mt-4">
            <thead><tr><th>Mes</th><th class="text-right">Contrataciones</th><th class="text-right">Facturado</th><th class="text-right">Comisión</th></tr></thead>
            <tbody>
            @foreach ($meses as $m)
                @continue(! isset($comisionMes[$m]))
                <tr><td>{{ mes_corto($m) }}</td><td class="text-right">{{ $comisionMes[$m]->cantidad }}</td><td class="text-right">{{ dinero($comisionMes[$m]->facturado, 2) }}</td><td class="text-right font-medium">{{ dinero($comisionMes[$m]->comision, 2) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="card h-fit lg:col-span-2">
        <h2 class="text-xl font-bold">Músicos con más contrataciones</h2>
        <table class="tabla mt-3">
            <thead><tr><th>#</th><th>Músico</th><th class="text-right">Contrat.</th><th class="text-right">Ingresos</th></tr></thead>
            <tbody>
            @forelse ($topMusicos as $i => $m)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><a href="{{ route('musicos.show', $m->id) }}" class="hover:text-marca-600">{{ $m->nombre_artistico }}</a> @if ($m->verificado)<span class="text-green-600">✓</span>@endif</td>
                    <td class="text-right">{{ $m->contrataciones }}</td>
                    <td class="text-right">{{ dinero($m->ingresos) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-6 text-center text-slate-500">Sin datos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
