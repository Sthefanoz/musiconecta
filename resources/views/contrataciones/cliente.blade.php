@extends('layouts.app')
@section('title', 'Mis contrataciones')

@section('content')
<h1 class="titulo">Mis contrataciones</h1>
<p class="subtitulo">Paga el anticipo cuando el músico acepte, el saldo después del evento y deja tu reseña.</p>

<div class="card mt-6 overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Fecha</th><th>Músico / banda</th><th>Servicio</th><th>Evento</th><th class="text-right">Total</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($contrataciones as $c)
            @php $estado = $c->estado; @endphp
            <tr>
                <td class="whitespace-nowrap">{{ $c->fecha_evento->format('d/m/Y') }} {{ substr($c->hora_inicio, 0, 5) }}</td>
                <td>{{ $c->servicio->proveedorNombre() }}</td>
                <td>{{ $c->servicio->nombre }} ({{ $c->horas + 0 }} h)</td>
                <td>{{ $c->tipoEvento->nombre }}</td>
                <td class="text-right">{{ dinero($c->monto_total, 2) }}</td>
                <td><span class="badge {{ $estado->badge() }}">{{ $estado->label() }}</span></td>
                <td class="space-y-1">
                    @if ($estado === \App\Enums\EstadoContratacion::Aceptada)
                        @include('contrataciones._pago', ['ruta' => route('contrataciones.anticipo', $c), 'texto' => 'Pagar anticipo', 'monto' => $c->montoAnticipo()])
                    @elseif ($estado === \App\Enums\EstadoContratacion::Confirmada && ! $c->tienePago('saldo') && $c->inicio()->isPast())
                        @include('contrataciones._pago', ['ruta' => route('contrataciones.saldo', $c), 'texto' => 'Pagar saldo', 'monto' => $c->montoSaldo()])
                    @elseif ($estado === \App\Enums\EstadoContratacion::Realizada && ! $c->resena)
                        <a href="{{ route('contrataciones.show', $c) }}#resena" class="btn btn-success btn-sm">Calificar</a>
                    @endif
                    <a href="{{ route('contrataciones.show', $c) }}" class="btn btn-light btn-sm">Ver detalle</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="py-8 text-center text-slate-500">Aún no tienes contrataciones. <a href="{{ route('home') }}" class="text-marca-600 hover:underline">Busca músicos</a>.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
