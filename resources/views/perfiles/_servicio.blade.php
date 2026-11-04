<div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
    <div>
        <p class="font-semibold">{{ $s->nombre }}
            @isset($conBanda)<span class="text-sm font-normal text-slate-500">· con {{ $s->banda->nombre }}</span>@endisset
        </p>
        <p class="text-sm text-slate-500">{{ $s->tipo->nombre }} · {{ $s->duracion_horas + 0 }} h · {{ dinero($s->precio_base) }}</p>
        @if ($s->descripcion)<p class="mt-1 text-sm text-slate-600">{{ $s->descripcion }}</p>@endif
    </div>
    @guest
        <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Inicia sesión para contratar</a>
    @else
        @if (auth()->user()->esCliente())
            <a href="{{ route('contrataciones.create', $s) }}" class="btn btn-primary btn-sm">Contratar</a>
        @endif
    @endguest
</div>
