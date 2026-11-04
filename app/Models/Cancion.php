<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cancion extends Model
{
    protected $table = 'canciones';

    protected $fillable = ['genero_id', 'titulo', 'artista_original'];

    /** Estilo de la canción. */
    public function genero(): BelongsTo
    {
        return $this->belongsTo(Genero::class);
    }
}
