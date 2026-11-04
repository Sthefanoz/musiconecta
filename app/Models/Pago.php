<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = ['contratacion_id', 'tipo', 'monto', 'metodo', 'pagado_en'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'pagado_en' => 'datetime'];
    }

    public function contratacion(): BelongsTo
    {
        return $this->belongsTo(Contratacion::class);
    }
}
