<?php

namespace App\Enums;

enum Rol: string
{
    case Admin = 'admin';
    case Musico = 'musico';
    case Cliente = 'cliente';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Musico => 'Músico',
            self::Cliente => 'Cliente',
        };
    }
}
