<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Banda extends Model
{
    protected $table = 'bandas';

    protected $fillable = ['lider_id', 'genero_id', 'ciudad_id', 'nombre', 'descripcion'];

    public function lider(): BelongsTo
    {
        return $this->belongsTo(Musico::class, 'lider_id');
    }

    public function genero(): BelongsTo
    {
        return $this->belongsTo(Genero::class);
    }

    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class);
    }

    public function integrantes(): BelongsToMany
    {
        return $this->belongsToMany(Musico::class, 'banda_musico')
            ->withPivot(['instrumento', 'porcentaje_pago']);
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }

    public function repertorio(): BelongsToMany
    {
        return $this->belongsToMany(Cancion::class, 'repertorio', 'banda_id', 'cancion_id');
    }

    /** Los porcentajes de pago de los integrantes deben sumar exactamente 100 %. */
    public function porcentajesValidos(): bool
    {
        $integrantes = $this->integrantes()->get();

        return $integrantes->isNotEmpty()
            && abs($integrantes->sum(fn ($m) => (float) $m->pivot->porcentaje_pago) - 100) < 0.001;
    }

    public function estaDisponible(Carbon $inicio, Carbon $fin, ?int $ignorarContratacionId = null): bool
    {
        return $this->integrantes->every(fn (Musico $m) => $m->estaDisponible($inicio, $fin, $ignorarContratacionId));
    }

    public function esLider(?Musico $musico): bool
    {
        return $musico !== null && $this->lider_id === $musico->id;
    }
}
