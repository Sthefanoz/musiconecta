<?php

namespace App\Exceptions;

use RuntimeException;

/** Error de una regla del negocio (disponibilidad, transición de estado, porcentajes, etc.). */
class ReglaNegocioException extends RuntimeException
{
}
