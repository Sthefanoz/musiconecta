<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloqueoAgenda extends Model
{
    protected $table = 'bloqueos_agenda';

    protected $fillable = ['musico_id', 'contratacion_id', 'inicio', 'fin', 'motivo'];

    protected function casts(): array
    {
        return ['inicio' => 'datetime', 'fin' => 'datetime'];
    }

    public function musico(): BelongsTo
    {
        return $this->belongsTo(Musico::class);
    }

    public function contratacion(): BelongsTo
    {
        return $this->belongsTo(Contratacion::class);
    }
}
