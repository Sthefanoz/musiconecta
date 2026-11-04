<?php

namespace App\Services;

use App\Models\BloqueoAgenda;
use App\Models\Contratacion;
use App\Models\Musico;
use App\Models\Servicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AgendaService
{
    /**
     * Músicos del servicio que NO están disponibles en el intervalo.
     * Para un solista se revisa su agenda; para una banda, la de todos sus integrantes.
     *
     * @return Collection<int, Musico>
     */
    public function musicosOcupados(Servicio $servicio, Carbon $inicio, Carbon $fin, ?int $ignorarContratacionId = null): Collection
    {
        return $servicio->musicosParticipantes()
            ->reject(fn (Musico $m) => $m->estaDisponible($inicio, $fin, $ignorarContratacionId))
            ->values();
    }

    public function hayConflicto(Servicio $servicio, Carbon $inicio, Carbon $fin, ?int $ignorarContratacionId = null): bool
    {
        return $this->musicosOcupados($servicio, $inicio, $fin, $ignorarContratacionId)->isNotEmpty();
    }

    /** Bloquea la agenda de cada músico participante con la contratación. */
    public function bloquear(Contratacion $contratacion): void
    {
        $inicio = $contratacion->inicio();
        $fin = $contratacion->fin();

        foreach ($contratacion->servicio->musicosParticipantes() as $musico) {
            BloqueoAgenda::updateOrCreate(
                ['musico_id' => $musico->id, 'contratacion_id' => $contratacion->id],
                ['inicio' => $inicio, 'fin' => $fin, 'motivo' => 'Contratación #'.$contratacion->id.' · '.$contratacion->servicio->nombre],
            );
        }
    }

    /** Libera la agenda ocupada por la contratación (rechazo o cancelación). */
    public function liberar(Contratacion $contratacion): void
    {
        BloqueoAgenda::where('contratacion_id', $contratacion->id)->delete();
    }

    /** Mensaje de disponibilidad para mostrar al cliente. */
    public function mensajeDisponibilidad(Servicio $servicio, Carbon $inicio, Carbon $fin): array
    {
        $ocupados = $this->musicosOcupados($servicio, $inicio, $fin);
        $rango = $inicio->format('d/m').' de '.$inicio->format('H:i').' a '.$fin->format('H:i');

        if ($ocupados->isEmpty()) {
            $texto = $servicio->esDeBanda()
                ? 'Los '.$servicio->banda->integrantes->count().' integrantes están disponibles el '.$rango.'.'
                : $servicio->musico->nombre_artistico.' está disponible el '.$rango.'.';

            return ['disponible' => true, 'mensaje' => $texto];
        }

        $nombres = $ocupados->pluck('nombre_artistico')->join(', ', ' y ');

        return [
            'disponible' => false,
            'mensaje' => ($ocupados->count() > 1 ? 'No están disponibles: ' : 'No está disponible: ').$nombres.' ('.$rango.').',
        ];
    }
}
