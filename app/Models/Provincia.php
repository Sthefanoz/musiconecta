<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provincia extends Model
{
    protected $table = 'provincias';

    protected $fillable = ['nombre'];

    public function ciudades(): HasMany
    {
        return $this->hasMany(Ciudad::class)->orderBy('nombre');
    }
}
