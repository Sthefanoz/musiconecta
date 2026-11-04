<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resena extends Model
{
    protected $table = 'resenas';

    protected $fillable = ['contratacion_id', 'puntuacion', 'comentario'];

    protected function casts(): array
    {
        return ['puntuacion' => 'integer'];
    }

    public function contratacion(): BelongsTo
    {
        return $this->belongsTo(Contratacion::class);
    }
}
