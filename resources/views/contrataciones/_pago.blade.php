{{-- Formulario de pago simulado: $ruta, $texto, $monto --}}
<form method="POST" action="{{ $ruta }}" class="flex flex-wrap items-center gap-2">
    @csrf
    <select name="metodo" class="input w-auto py-1.5 text-xs">
        @foreach (config('musiconecta.metodos_pago') as $valor => $nombre)<option value="{{ $valor }}">{{ $nombre }}</option>@endforeach
    </select>
    <button class="btn btn-primary btn-sm">{{ $texto }} {{ dinero($monto, 2) }}</button>
</form>
