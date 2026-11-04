<?php

namespace App\Enums;

enum EstadoContratacion: string
{
    case Solicitada = 'solicitada';
    case Aceptada = 'aceptada';
    case Confirmada = 'confirmada';
    case Realizada = 'realizada';
    case Rechazada = 'rechazada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Clases Tailwind del badge. */
    public function badge(): string
    {
        return match ($this) {
            self::Solicitada => 'bg-amber-100 text-amber-800',
            self::Aceptada => 'bg-sky-100 text-sky-800',
            self::Confirmada => 'bg-indigo-100 text-indigo-700',
            self::Realizada => 'bg-green-100 text-green-800',
            self::Rechazada => 'bg-red-100 text-red-800',
            self::Cancelada => 'bg-slate-200 text-slate-600',
        };
    }

    /** Transiciones permitidas del flujo de contratación. */
    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::Solicitada => [self::Aceptada, self::Rechazada, self::Cancelada],
            self::Aceptada => [self::Confirmada, self::Cancelada],
            self::Confirmada => [self::Realizada, self::Cancelada],
            default => [],
        };
    }

    public function puedeCambiarA(self $nuevo): bool
    {
        return in_array($nuevo, $this->transicionesPermitidas(), true);
    }

    /** Estados que ocupan la agenda del músico. */
    public static function ocupanAgenda(): array
    {
        return [self::Aceptada->value, self::Confirmada->value];
    }
}
