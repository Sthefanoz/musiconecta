<?php

namespace App\Models;

use App\Enums\EstadoContratacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Contratacion extends Model
{
    protected $table = 'contrataciones';

    protected $fillable = [
        'cliente_id', 'servicio_id', 'tipo_evento_id', 'ciudad_id', 'fecha_evento',
        'hora_inicio', 'horas', 'direccion', 'monto_total', 'comision', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_evento' => 'date',
            'horas' => 'float',
            'monto_total' => 'decimal:2',
            'comision' => 'decimal:2',
            'estado' => EstadoContratacion::class,
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function tipoEvento(): BelongsTo
    {
        return $this->belongsTo(TipoEvento::class);
    }

    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class);
    }

    public function canciones(): BelongsToMany
    {
        return $this->belongsToMany(Cancion::class, 'contratacion_cancion');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function repartos(): HasMany
    {
        return $this->hasMany(Reparto::class);
    }

    public function resena(): HasOne
    {
        return $this->hasOne(Resena::class);
    }

    /** Contrataciones en las que participa un músico: sus servicios de solista o los de sus bandas. */
    public function scopeDeMusico(Builder $query, Musico $musico): Builder
    {
        $bandaIds = $musico->bandaIds();

        return $query->whereHas('servicio', fn ($s) => $s
            ->where('musico_id', $musico->id)
            ->when($bandaIds, fn ($q) => $q->orWhereIn('banda_id', $bandaIds)));
    }

    public function inicio(): Carbon
    {
        return Carbon::parse($this->fecha_evento->format('Y-m-d').' '.$this->hora_inicio);
    }

    public function fin(): Carbon
    {
        return $this->inicio()->addMinutes((int) round($this->horas * 60));
    }

    public function ingresoNeto(): float
    {
        return round((float) $this->monto_total - (float) $this->comision, 2);
    }

    public function montoAnticipo(): float
    {
        return round((float) $this->monto_total * config('musiconecta.anticipo'), 2);
    }

    public function montoSaldo(): float
    {
        return round((float) $this->monto_total - $this->montoAnticipo(), 2);
    }

    public function puedeCambiarA(EstadoContratacion $estado): bool
    {
        return $this->estado->puedeCambiarA($estado);
    }

    public function tienePago(string $tipo): bool
    {
        return $this->pagos->contains('tipo', $tipo);
    }

    /** Porcentaje que le corresponde a un músico en esta contratación. */
    public function porcentajeDe(Musico $musico): ?float
    {
        $reparto = $this->repartos->firstWhere('musico_id', $musico->id);
        if ($reparto) {
            return (float) $reparto->porcentaje;
        }

        return $this->servicio->participantes()->get($musico->id);
    }

    /** Parte del ingreso neto que le toca al músico (real si ya hay reparto, estimada si no). */
    public function parteDe(Musico $musico): float
    {
        $reparto = $this->repartos->firstWhere('musico_id', $musico->id);
        if ($reparto) {
            return (float) $reparto->monto_neto;
        }

        return round($this->ingresoNeto() * ($this->porcentajeDe($musico) ?? 0) / 100, 2);
    }

    public function esResponsable(?Musico $musico): bool
    {
        return $musico !== null && $this->servicio->responsableId() === $musico->id;
    }

    public function participa(?Musico $musico): bool
    {
        return $musico !== null && $this->servicio->participantes()->has($musico->id);
    }
}
