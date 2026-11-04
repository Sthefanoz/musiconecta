<div class="card">
    <h2 class="text-lg font-bold">Reseñas</h2>
    <div class="mt-4 space-y-4">
        @forelse ($resenas as $r)
            <div class="border-b border-slate-100 pb-4 last:border-0">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-semibold">{{ $r->contratacion->cliente->name }}</span>
                    <span class="text-amber-500">{{ str_repeat('★', $r->puntuacion) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $r->puntuacion) }}</span></span>
                </div>
                <p class="text-xs text-slate-400">{{ $r->contratacion->servicio->nombre }} · {{ $r->contratacion->tipoEvento->nombre }} · {{ $r->contratacion->fecha_evento->format('d/m/Y') }}</p>
                @if ($r->comentario)<p class="mt-1 text-sm text-slate-700">{{ $r->comentario }}</p>@endif
            </div>
        @empty
            <p class="text-sm text-slate-500">Todavía no tiene reseñas.</p>
        @endforelse
    </div>
</div>
