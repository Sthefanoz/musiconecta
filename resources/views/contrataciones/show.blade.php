@extends('layouts.app')
@section('title', 'Contratación #'.$c->id)

@use('App\Enums\EstadoContratacion', 'E')

@section('content')
<div class="flex flex-wrap items-center gap-3">
    <h1 class="titulo">Contratación #{{ $c->id }}</h1>
    <span class="badge {{ $c->estado->badge() }} text-sm">{{ $c->estado->label() }}</span>
</div>
<p class="subtitulo">{{ $c->servicio->nombre }} · {{ $c->servicio->proveedorNombre() }} · solicitada por {{ $c->cliente->name }}</p>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="card">
            <h2 class="text-lg font-bold">Detalle del evento</h2>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-slate-500">Tipo de evento</dt><dd class="font-medium">{{ $c->tipoEvento->nombre }}</dd></div>
                <div><dt class="text-slate-500">Fecha y hora</dt><dd class="font-medium">{{ $c->inicio()->format('d/m/Y H:i') }} – {{ $c->fin()->format('H:i') }}</dd></div>
                <div><dt class="text-slate-500">Duración</dt><dd class="font-medium">{{ $c->horas + 0 }} h</dd></div>
                <div><dt class="text-slate-500">Ciudad</dt><dd class="font-medium">{{ $c->ciudad->nombre }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500">Dirección</dt><dd class="font-medium">{{ $c->direccion }}</dd></div>
                <div><dt class="text-slate-500">Cliente</dt><dd class="font-medium">{{ $c->cliente->name }}</dd></div>
                <div><dt class="text-slate-500">Contacto</dt><dd class="font-medium">{{ $c->cliente->telefono ?? $c->cliente->email }}</dd></div>
            </dl>

            <h3 class="mt-6 text-sm font-semibold">Canciones solicitadas ({{ $c->canciones->count() }})</h3>
            <ul class="mt-2 grid gap-1 text-sm sm:grid-cols-2">
                @forelse ($c->canciones as $can)
                    <li>{{ $can->titulo }} <span class="text-slate-400">· {{ $can->genero->nombre }}</span></li>
                @empty
                    <li class="text-slate-500">Sin canciones específicas.</li>
                @endforelse
            </ul>
        </div>

        <div class="card">
            <h2 class="text-lg font-bold">Reparto del pago</h2>
            <p class="text-sm text-slate-500">
                Ingreso neto = total {{ dinero($c->monto_total, 2) }} − comisión {{ config('musiconecta.comision') * 100 }} % ({{ dinero($c->comision, 2) }}) = <b>{{ dinero($c->ingresoNeto(), 2) }}</b>
                @if ($c->repartos->isEmpty()) · <i>estimado, se genera al marcarla como realizada</i>@endif
            </p>
            <table class="tabla mt-3">
                <thead><tr><th>Músico</th><th class="text-right">Porcentaje</th><th class="text-right">Monto neto</th></tr></thead>
                <tbody>
                @if ($c->repartos->isNotEmpty())
                    @foreach ($c->repartos as $r)
                        <tr @class(['bg-marca-50' => $musico && $r->musico_id === $musico->id])><td>{{ $r->musico->nombre_artistico }}</td><td class="text-right">{{ pct($r->porcentaje, 0) }}</td><td class="text-right">{{ dinero($r->monto_neto, 2) }}</td></tr>
                    @endforeach
                @else
                    @foreach ($c->servicio->musicosParticipantes() as $m)
                        <tr @class(['bg-marca-50' => $musico && $m->id === $musico->id])><td>{{ $m->nombre_artistico }}</td><td class="text-right">{{ pct($c->porcentajeDe($m), 0) }}</td><td class="text-right">{{ dinero($c->parteDe($m), 2) }}</td></tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <h2 class="text-lg font-bold">Pagos</h2>
            <table class="mt-3 w-full text-sm">
                <tr class="border-b border-slate-100"><td class="py-2">Total</td><td class="py-2 text-right font-semibold">{{ dinero($c->monto_total, 2) }}</td></tr>
                <tr class="border-b border-slate-100"><td class="py-2">Anticipo ({{ config('musiconecta.anticipo') * 100 }} %)</td><td class="py-2 text-right">{{ dinero($c->montoAnticipo(), 2) }} {!! $c->tienePago('anticipo') ? '<span class="text-green-600">✓</span>' : '' !!}</td></tr>
                <tr><td class="py-2">Saldo</td><td class="py-2 text-right">{{ dinero($c->montoSaldo(), 2) }} {!! $c->tienePago('saldo') ? '<span class="text-green-600">✓</span>' : '' !!}</td></tr>
            </table>
            @foreach ($c->pagos as $p)
                <p class="mt-1 text-xs text-slate-500">{{ ucfirst($p->tipo) }} pagado el {{ $p->pagado_en->format('d/m/Y H:i') }} · {{ config('musiconecta.metodos_pago')[$p->metodo] ?? $p->metodo }}</p>
            @endforeach
        </div>

        {{-- Acciones del cliente --}}
        @if ($esCliente)
            <div class="card space-y-3">
                <h2 class="text-lg font-bold">Acciones</h2>
                @if ($c->estado === E::Aceptada)
                    @include('contrataciones._pago', ['ruta' => route('contrataciones.anticipo', $c), 'texto' => 'Pagar anticipo', 'monto' => $c->montoAnticipo()])
                @endif
                @if ($c->estado === E::Confirmada && ! $c->tienePago('saldo'))
                    @if ($c->inicio()->isPast())
                        @include('contrataciones._pago', ['ruta' => route('contrataciones.saldo', $c), 'texto' => 'Pagar saldo', 'monto' => $c->montoSaldo()])
                    @else
                        <p class="text-sm text-slate-500">El saldo se paga después del evento.</p>
                    @endif
                @endif
                @if (in_array($c->estado, [E::Solicitada, E::Aceptada, E::Confirmada]) && $c->inicio()->isFuture())
                    <form method="POST" action="{{ route('contrataciones.cancelar', $c) }}" onsubmit="return confirm('¿Cancelar esta contratación?')">
                        @csrf <button class="btn btn-light btn-sm text-red-700">Cancelar contratación</button>
                    </form>
                @endif
                @if ($c->estado === E::Realizada && ! $c->resena)
                    <form method="POST" action="{{ route('contrataciones.resena', $c) }}" id="resena" class="space-y-3">
                        @csrf
                        <label class="label">Tu calificación</label>
                        <select name="puntuacion" class="input">
                            @for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ str_repeat('★', $i) }} ({{ $i }})</option>@endfor
                        </select>
                        <textarea name="comentario" rows="3" class="input" placeholder="¿Cómo te fue?"></textarea>
                        <button class="btn btn-success">Publicar reseña</button>
                    </form>
                @endif
            </div>
        @endif

        {{-- Acciones del músico responsable --}}
        @if ($esResponsable)
            <div class="card space-y-3">
                <h2 class="text-lg font-bold">Acciones</h2>
                @if ($c->estado === E::Solicitada)
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('musico.solicitudes.aceptar', $c) }}">@csrf <button class="btn btn-primary btn-sm">Aceptar</button></form>
                        <form method="POST" action="{{ route('musico.solicitudes.rechazar', $c) }}">@csrf <button class="btn btn-danger btn-sm">Rechazar</button></form>
                    </div>
                @elseif ($c->estado === E::Confirmada)
                    @if ($c->tienePago('saldo'))
                        <form method="POST" action="{{ route('musico.solicitudes.realizada', $c) }}">@csrf <button class="btn btn-success btn-sm">Marcar como realizada</button></form>
                    @else
                        <p class="text-sm text-slate-500">Podrás marcarla como realizada cuando el cliente pague el saldo.</p>
                    @endif
                @elseif ($c->estado === E::Aceptada)
                    <p class="text-sm text-slate-500">Esperando el pago del anticipo.</p>
                @else
                    <p class="text-sm text-slate-500">Sin acciones pendientes.</p>
                @endif
            </div>
        @endif

        @if ($c->resena)
            <div class="card">
                <h2 class="text-lg font-bold">Reseña</h2>
                <p class="mt-2 text-amber-500">{{ str_repeat('★', $c->resena->puntuacion) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $c->resena->puntuacion) }}</span></p>
                <p class="mt-1 text-sm text-slate-700">{{ $c->resena->comentario }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
