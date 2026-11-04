<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Genero extends Model
{
    protected $table = 'generos';

    protected $fillable = ['nombre'];

    public function canciones(): HasMany
    {
        return $this->hasMany(Cancion::class);
    }
}
