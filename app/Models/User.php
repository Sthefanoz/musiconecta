<?php

namespace App\Models;

use App\Enums\Rol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'telefono', 'rol'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
        ];
    }

    public function musico(): HasOne
    {
        return $this->hasOne(Musico::class);
    }

    /** Contrataciones solicitadas por el usuario como cliente. */
    public function contrataciones(): HasMany
    {
        return $this->hasMany(Contratacion::class, 'cliente_id');
    }

    public function tieneRol(string|Rol ...$roles): bool
    {
        foreach ($roles as $rol) {
            $valor = $rol instanceof Rol ? $rol : Rol::from($rol);
            if ($this->rol === $valor) {
                return true;
            }
        }

        return false;
    }

    public function esAdmin(): bool
    {
        return $this->rol === Rol::Admin;
    }

    public function esMusico(): bool
    {
        return $this->rol === Rol::Musico;
    }

    public function esCliente(): bool
    {
        return $this->rol === Rol::Cliente;
    }
}
