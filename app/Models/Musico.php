<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Musico extends Model
{
    protected $table = 'musicos';

    protected $fillable = [
        'user_id', 'ciudad_id', 'genero_principal_id', 'cedula', 'nombre_artistico',
        'biografia', 'anios_experiencia', 'verificado',
    ];

    protected $attributes = ['verificado' => false, 'anios_experiencia' => 0];

    protected function casts(): array
    {
        return ['verificado' => 'boolean', 'anios_experiencia' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class);
    }

    public function generoPrincipal(): BelongsTo
    {
        return $this->belongsTo(Genero::class, 'genero_principal_id');
    }

    public function generos(): BelongsToMany
    {
        return $this->belongsToMany(Genero::class, 'musico_genero');
    }

    /** Bandas en las que participa como integrante. */
    public function bandas(): BelongsToMany
    {
        return $this->belongsToMany(Banda::class, 'banda_musico')
            ->withPivot(['instrumento', 'porcentaje_pago']);
    }

    public function bandasLideradas(): HasMany
    {
        return $this->hasMany(Banda::class, 'lider_id');
    }

    /** Servicios que ofrece como solista. */
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }

    /** Repertorio personal (filas de repertorio con musico_id). */
    public function repertorio(): BelongsToMany
    {
        return $this->belongsToMany(Cancion::class, 'repertorio', 'musico_id', 'cancion_id');
    }

    public function bloqueos(): HasMany
    {
        return $this->hasMany(BloqueoAgenda::class);
    }

    public function repartos(): HasMany
    {
        return $this->hasMany(Reparto::class);
    }

    /** IDs de las bandas del músico. */
    public function bandaIds(): array
    {
        return $this->bandas()->pluck('bandas.id')->all();
    }

    /** Contrataciones en las que participa (como solista o con alguna de sus bandas). */
    public function contrataciones(): Builder
    {
        return Contratacion::query()->deMusico($this);
    }

    /** Disponibilidad del músico entre dos instantes. */
    public function estaDisponible(Carbon $inicio, Carbon $fin, ?int $ignorarContratacionId = null): bool
    {
        return ! $this->bloqueos()
            ->where('inicio', '<', $fin)
            ->where('fin', '>', $inicio)
            ->when($ignorarContratacionId, fn ($q) => $q->where(fn ($w) => $w
                ->whereNull('contratacion_id')
                ->orWhere('contratacion_id', '!=', $ignorarContratacionId)))
            ->exists();
    }

    public function calificacionPromedio(): ?float
    {
        $prom = Resena::whereIn('contratacion_id', $this->contrataciones()->select('contrataciones.id'))->avg('puntuacion');

        return $prom !== null ? round((float) $prom, 1) : null;
    }

    public function cantidadResenas(): int
    {
        return Resena::whereIn('contratacion_id', $this->contrataciones()->select('contrataciones.id'))->count();
    }
}
