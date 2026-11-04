<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reparto extends Model
{
    protected $table = 'repartos';

    public $timestamps = false;

    protected $fillable = ['contratacion_id', 'musico_id', 'porcentaje', 'monto_neto'];

    protected function casts(): array
    {
        return ['porcentaje' => 'decimal:2', 'monto_neto' => 'decimal:2'];
    }

    public function contratacion(): BelongsTo
    {
        return $this->belongsTo(Contratacion::class);
    }

    public function musico(): BelongsTo
    {
        return $this->belongsTo(Musico::class);
    }
}
