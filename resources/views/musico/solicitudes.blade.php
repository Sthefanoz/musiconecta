@extends('layouts.app')
@section('title', 'Solicitudes')

@use('App\Enums\EstadoContratacion', 'E')

@section('content')
<h1 class="titulo">Solicitudes y contrataciones</h1>
<p class="subtitulo">Acepta o rechaza solicitudes y marca como realizadas las contrataciones cumplidas.</p>

<nav class="mt-6 flex gap-8 border-b border-slate-200">
    @foreach (['todas' => 'Todas', 'pendientes' => 'Por responder ('.$conteoPendientes.')', 'confirmadas' => 'Confirmadas', 'realizadas' => 'Realizadas'] as $clave => $texto)
        <a href="{{ route('musico.solicitudes.index', ['filtro' => $clave]) }}" class="tab {{ $filtro === $clave ? 'tab-activa' : '' }}">{{ $texto }}</a>
    @endforeach
</nav>

<div class="card mt-6 overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Fecha</th><th>Cliente</th><th>Servicio</th><th>Como</th><th>Tipo de evento</th><th class="text-right">Tu parte</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($contrataciones as $c)
            <tr>
                <td class="whitespace-nowrap">{{ $c->fecha_evento->format('d/m/Y') }} {{ substr($c->hora_inicio, 0, 5) }}</td>
                <td>{{ $c->cliente->name }}</td>
                <td>{{ $c->servicio->nombre }} @if ($c->horas != $c->servicio->duracion_horas)<span class="text-slate-400">({{ $c->horas + 0 }} h)</span>@endif</td>
                <td>{{ $c->servicio->esDeBanda() ? $c->servicio->banda->nombre.' ('.pct($c->porcentajeDe($musico), 0).')' : 'Solista' }}</td>
                <td>{{ $c->tipoEvento->nombre }}</td>
                <td class="text-right font-medium">{{ dinero($c->parteDe($musico), 2) }}</td>
                <td><span class="badge {{ $c->estado->badge() }}">{{ $c->estado->label() }}</span></td>
                <td>
                    <div class="flex gap-2">
                        @if ($c->estado === E::Solicitada && $c->esResponsable($musico))
                            <form method="POST" action="{{ route('musico.solicitudes.aceptar', $c) }}">@csrf <button class="btn btn-primary btn-sm">Aceptar</button></form>
                            <form method="POST" action="{{ route('musico.solicitudes.rechazar', $c) }}">@csrf <button class="btn btn-danger btn-sm">Rechazar</button></form>
                        @elseif ($c->estado === E::Confirmada && $c->esResponsable($musico) && $c->tienePago('saldo'))
                            <form method="POST" action="{{ route('musico.solicitudes.realizada', $c) }}">@csrf <button class="btn btn-success btn-sm">Marcar realizada</button></form>
                        @endif
                        <a href="{{ route('contrataciones.show', $c) }}" class="btn btn-light btn-sm">Ver detalle</a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="py-8 text-center text-slate-500">No hay contrataciones en esta vista.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $contrataciones->links() }}</div>
</div>
@endsection
