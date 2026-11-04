@extends('layouts.app')
@section('title', $servicio->exists ? 'Editar servicio' : 'Nuevo servicio')

@section('content')
<h1 class="titulo">{{ $servicio->exists ? 'Editar servicio' : 'Nuevo servicio' }}</h1>

<form method="POST" action="{{ $servicio->exists ? route('musico.servicios.update', $servicio) : route('musico.servicios.store') }}" class="card mt-6 max-w-3xl space-y-4">
    @csrf
    @if ($servicio->exists) @method('PUT') @endif
    @php $como = old('ofrecido_como', $servicio->banda_id ? (string) $servicio->banda_id : 'solista'); @endphp

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Tipo de servicio</label>
            <select class="input" name="tipo_servicio_id" required>
                @foreach ($tipos as $t)<option value="{{ $t->id }}" @selected(old('tipo_servicio_id', $servicio->tipo_servicio_id) == $t->id)>{{ $t->nombre }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label">Lo ofreces como</label>
            <select class="input" name="ofrecido_como" required>
                <option value="solista" @selected($como === 'solista')>Solista</option>
                @foreach ($bandas as $b)<option value="{{ $b->id }}" @selected($como === (string) $b->id)>Con la banda {{ $b->nombre }}</option>@endforeach
            </select>
            @if ($bandas->isEmpty())<p class="mt-1 text-xs text-slate-500">Para ofrecer servicios con una banda debes ser su líder.</p>@endif
        </div>
        <div class="sm:col-span-2">
            <label class="label">Nombre</label>
            <input class="input" name="nombre" value="{{ old('nombre', $servicio->nombre) }}" placeholder="Ej.: Serenata romántica" required>
        </div>
        <div>
            <label class="label">Precio base (USD)</label>
            <input class="input" type="number" step="0.01" min="1" name="precio_base" value="{{ old('precio_base', $servicio->precio_base) }}" required>
        </div>
        <div>
            <label class="label">Duración base (horas)</label>
            <input class="input" type="number" step="0.5" min="0.5" max="24" name="duracion_horas" value="{{ old('duracion_horas', $servicio->duracion_horas) }}" required>
            <p class="mt-1 text-xs text-slate-500">Si el cliente pide más o menos horas, el precio se ajusta proporcionalmente.</p>
        </div>
    </div>
    <div>
        <label class="label">Descripción</label>
        <textarea class="input" name="descripcion" rows="4">{{ old('descripcion', $servicio->descripcion) }}</textarea>
    </div>
    <div class="flex gap-2">
        <button class="btn btn-primary">Guardar</button>
        <a href="{{ route('musico.servicios.index') }}" class="btn btn-light">Cancelar</a>
    </div>
</form>
@endsection
