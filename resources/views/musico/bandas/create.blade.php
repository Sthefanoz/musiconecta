@extends('layouts.app')
@section('title', 'Crear banda')

@section('content')
<h1 class="titulo">Crear banda</h1>
<p class="subtitulo">Serás el líder. Luego podrás agregar integrantes registrados y definir el porcentaje de pago de cada uno.</p>

<form method="POST" action="{{ route('musico.bandas.store') }}" class="card mt-6 max-w-3xl space-y-4">
    @csrf
    @include('musico.bandas._campos', ['banda' => null])
    <div>
        <label class="label">Tu instrumento en la banda</label>
        <input class="input" name="instrumento" value="{{ old('instrumento') }}" placeholder="Ej.: Guitarra y voz" required>
    </div>
    <button class="btn btn-primary">Crear banda</button>
</form>
@endsection
