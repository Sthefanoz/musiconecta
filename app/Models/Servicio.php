<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Lo ofrece un músico (solista) o una banda: solo uno de los dos FK tiene valor.
 */
class Servicio extends Model
{
    protected $table = 'servicios';

    protected $fillable = [
        'musico_id', 'banda_id', 'tipo_servicio_id', 'nombre', 'descripcion',
        'precio_base', 'duracion_horas', 'activo',
    ];

    protected $attributes = ['activo' => true];

    protected function casts(): array
    {
        return [
            'precio_base' => 'decimal:2',
            'duracion_horas' => 'float',
            'activo' => 'boolean',
        ];
    }

    public function musico(): BelongsTo
    {
        return $this->belongsTo(Musico::class);
    }

    public function banda(): BelongsTo
    {
        return $this->belongsTo(Banda::class);
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoServicio::class, 'tipo_servicio_id');
    }

    public function contrataciones(): HasMany
    {
        return $this->hasMany(Contratacion::class);
    }

    public function esDeBanda(): bool
    {
        return $this->banda_id !== null;
    }

    /** Precio proporcional a las horas pedidas sobre la duración base del servicio. */
    public function cotizar(float $horas): float
    {
        $duracion = $this->duracion_horas > 0 ? $this->duracion_horas : 1;

        return round((float) $this->precio_base * $horas / $duracion, 2);
    }

    public function proveedorNombre(): string
    {
        return $this->esDeBanda() ? $this->banda->nombre : $this->musico->nombre_artistico;
    }

    /**
     * Músicos que participan y su porcentaje: [musico_id => porcentaje].
     * Solista: 100 %. Banda: el porcentaje acordado de cada integrante.
     */
    public function participantes(): Collection
    {
        if (! $this->esDeBanda()) {
            return collect([$this->musico_id => 100.0]);
        }

        return $this->banda->integrantes
            ->mapWithKeys(fn (Musico $m) => [$m->id => (float) $m->pivot->porcentaje_pago]);
    }

    /** Músicos (modelos) que participan en el servicio. */
    public function musicosParticipantes(): Collection
    {
        return $this->esDeBanda() ? $this->banda->integrantes : collect([$this->musico]);
    }

    /** Canciones que el cliente puede elegir. */
    public function repertorioDisponible(): Collection
    {
        $relacion = $this->esDeBanda() ? $this->banda->repertorio() : $this->musico->repertorio();

        return $relacion->with('genero')->orderBy('titulo')->get();
    }

    /** Músico responsable de responder solicitudes: el solista o el líder de la banda. */
    public function responsableId(): int
    {
        return $this->esDeBanda() ? $this->banda->lider_id : $this->musico_id;
    }
}
