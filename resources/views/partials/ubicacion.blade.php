{{--
    Dropdown dependiente Provincia → Ciudad.
    Uso: @include('partials.ubicacion', ['ciudadId' => $modelo?->ciudad_id])
    $provincias lo inyecta el View Composer de AppServiceProvider.
--}}
@php
    $ciudadSel = old('ciudad_id', $ciudadId ?? null);
    $ciudadModelo = $ciudadSel ? $provincias->flatMap->ciudades->firstWhere('id', (int) $ciudadSel) : null;
    $provinciaSel = old('provincia_id', $ciudadModelo?->provincia_id);
    $ciudadesIniciales = $provinciaSel ? ($provincias->firstWhere('id', (int) $provinciaSel)?->ciudades ?? collect()) : collect();
@endphp
<div class="grid gap-4 sm:grid-cols-2" data-ubicacion data-url="{{ url('/ubicacion/provincias/__ID__/ciudades') }}">
    <div>
        <label class="label" for="provincia_id">Provincia</label>
        <select class="input" name="provincia_id" id="provincia_id" data-provincia required>
            <option value="">Selecciona una provincia…</option>
            @foreach ($provincias as $p)
                <option value="{{ $p->id }}" @selected((int) $provinciaSel === $p->id)>{{ $p->nombre }}</option>
            @endforeach
        </select>
        @error('provincia_id')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="label" for="ciudad_id">Ciudad</label>
        <select class="input" name="ciudad_id" id="ciudad_id" data-ciudad required @disabled(! $provinciaSel)>
            <option value="">{{ $provinciaSel ? 'Selecciona una ciudad…' : 'Primero elige la provincia' }}</option>
            @foreach ($ciudadesIniciales as $c)
                <option value="{{ $c->id }}" @selected((int) $ciudadSel === $c->id)>{{ $c->nombre }}</option>
            @endforeach
        </select>
        @error('ciudad_id')<p class="error">{{ $message }}</p>@enderror
    </div>
</div>
