<?php

namespace App\Services;

use App\Enums\EstadoContratacion as Estado;
use App\Exceptions\ReglaNegocioException;
use App\Models\Contratacion;
use App\Models\Pago;
use App\Models\Reparto;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ContratacionService
{
    public function __construct(private AgendaService $agenda)
    {
    }

    /**
     * Registra una solicitud. Solo se guarda si el músico (o todos los integrantes de la banda) están disponibles.
     *
     * @param  array{tipo_evento_id:int, ciudad_id:int, fecha_evento:string, hora_inicio:string, horas:float|string, direccion:string}  $datos
     * @param  int[]  $cancionIds
     */
    public function solicitar(User $cliente, Servicio $servicio, array $datos, array $cancionIds = []): Contratacion
    {
        if (! $servicio->activo) {
            throw new ReglaNegocioException('El servicio no está activo.');
        }
        if ($servicio->esDeBanda() && ! $servicio->banda->porcentajesValidos()) {
            throw new ReglaNegocioException('La banda todavía no tiene definidos sus porcentajes de pago (deben sumar 100 %).');
        }

        $horas = (float) $datos['horas'];
        $inicio = Carbon::parse($datos['fecha_evento'].' '.$datos['hora_inicio']);
        $fin = $inicio->copy()->addMinutes((int) round($horas * 60));

        if ($inicio->isPast()) {
            throw new ReglaNegocioException('La fecha del evento debe ser futura.');
        }

        $disp = $this->agenda->mensajeDisponibilidad($servicio, $inicio, $fin);
        if (! $disp['disponible']) {
            throw new ReglaNegocioException($disp['mensaje']);
        }

        // Solo se aceptan canciones del repertorio del proveedor.
        $permitidas = $servicio->repertorioDisponible()->pluck('id')->all();
        $cancionIds = array_values(array_intersect(array_map('intval', $cancionIds), $permitidas));

        $monto = $servicio->cotizar($horas);

        return DB::transaction(function () use ($cliente, $servicio, $datos, $horas, $monto, $cancionIds) {
            $contratacion = Contratacion::create([
                'cliente_id' => $cliente->id,
                'servicio_id' => $servicio->id,
                'tipo_evento_id' => $datos['tipo_evento_id'],
                'ciudad_id' => $datos['ciudad_id'],
                'fecha_evento' => $datos['fecha_evento'],
                'hora_inicio' => $datos['hora_inicio'],
                'horas' => $horas,
                'direccion' => $datos['direccion'],
                'monto_total' => $monto,
                'comision' => round($monto * config('musiconecta.comision'), 2),
                'estado' => Estado::Solicitada,
            ]);
            $contratacion->canciones()->sync($cancionIds);

            return $contratacion;
        });
    }

    /** El músico (o el líder de la banda) acepta o rechaza la solicitud. */
    public function responder(Contratacion $contratacion, bool $acepta): void
    {
        $nuevo = $acepta ? Estado::Aceptada : Estado::Rechazada;
        $this->verificarTransicion($contratacion, $nuevo);

        DB::transaction(function () use ($contratacion, $acepta, $nuevo) {
            if ($acepta) {
                // Se vuelve a verificar: pudo ocuparse la agenda desde que se envió la solicitud.
                $ocupados = $this->agenda->musicosOcupados($contratacion->servicio, $contratacion->inicio(), $contratacion->fin(), $contratacion->id);
                if ($ocupados->isNotEmpty()) {
                    throw new ReglaNegocioException('No se puede aceptar: '.$ocupados->pluck('nombre_artistico')->join(', ').' ya tiene la fecha ocupada.');
                }
                $this->agenda->bloquear($contratacion);
            }
            $contratacion->update(['estado' => $nuevo]);
        });
    }

    /** El cliente paga el anticipo (simulado) y la contratación queda confirmada. */
    public function pagarAnticipo(Contratacion $contratacion, string $metodo): Pago
    {
        $this->verificarTransicion($contratacion, Estado::Confirmada);

        return DB::transaction(function () use ($contratacion, $metodo) {
            $pago = $contratacion->pagos()->create([
                'tipo' => 'anticipo',
                'monto' => $contratacion->montoAnticipo(),
                'metodo' => $metodo,
                'pagado_en' => now(),
            ]);
            $contratacion->update(['estado' => Estado::Confirmada]);
            $this->agenda->bloquear($contratacion);

            return $pago;
        });
    }

    /** El cliente paga el saldo (simulado) una vez iniciado el evento. */
    public function pagarSaldo(Contratacion $contratacion, string $metodo): Pago
    {
        $contratacion->loadMissing('pagos');

        if ($contratacion->estado !== Estado::Confirmada) {
            throw new ReglaNegocioException('Solo se puede pagar el saldo de una contratación confirmada.');
        }
        if ($contratacion->tienePago('saldo')) {
            throw new ReglaNegocioException('El saldo ya fue pagado.');
        }
        if ($contratacion->inicio()->isFuture()) {
            throw new ReglaNegocioException('El saldo se paga después del evento.');
        }

        return $contratacion->pagos()->create([
            'tipo' => 'saldo',
            'monto' => $contratacion->montoSaldo(),
            'metodo' => $metodo,
            'pagado_en' => now(),
        ]);
    }

    /** Marca la contratación como realizada y genera el reparto del pago entre los músicos. */
    public function marcarRealizada(Contratacion $contratacion): void
    {
        $contratacion->loadMissing('pagos');
        $this->verificarTransicion($contratacion, Estado::Realizada);

        if (! $contratacion->tienePago('saldo')) {
            throw new ReglaNegocioException('El cliente aún no paga el saldo.');
        }

        DB::transaction(function () use ($contratacion) {
            $contratacion->update(['estado' => Estado::Realizada]);
            $this->repartir($contratacion);
        });
    }

    /** El cliente cancela antes de la fecha del evento. */
    public function cancelar(Contratacion $contratacion): void
    {
        $this->verificarTransicion($contratacion, Estado::Cancelada);

        if ($contratacion->inicio()->isPast()) {
            throw new ReglaNegocioException('Solo se puede cancelar antes de la fecha del evento.');
        }

        DB::transaction(function () use ($contratacion) {
            $contratacion->update(['estado' => Estado::Cancelada]);
            $this->agenda->liberar($contratacion);
        });
    }

    /**
     * Genera un registro de reparto por músico.
     *   IN_k = M_k − c·M_k           (ingreso neto)
     *   solista: 100 % de IN_k
     *   banda:   IN_k · q_i / 100    (q_i = porcentaje acordado, Σ q_i = 100)
     * El último integrante absorbe el redondeo para que la suma sea exacta.
     *
     * @return Reparto[]
     */
    public function repartir(Contratacion $contratacion): array
    {
        $participantes = $contratacion->servicio->participantes();
        $suma = $participantes->sum();
        if (abs($suma - 100) > 0.001) {
            throw new ReglaNegocioException('Los porcentajes de la banda deben sumar 100 % (suman '.$suma.' %).');
        }

        $neto = $contratacion->ingresoNeto();
        $restante = $neto;
        $repartos = [];
        $ultimo = $participantes->keys()->last();

        $contratacion->repartos()->delete();

        foreach ($participantes as $musicoId => $porcentaje) {
            $monto = $musicoId === $ultimo ? round($restante, 2) : round($neto * $porcentaje / 100, 2);
            $restante -= $monto;

            $repartos[] = $contratacion->repartos()->create([
                'musico_id' => $musicoId,
                'porcentaje' => $porcentaje,
                'monto_neto' => $monto,
            ]);
        }

        return $repartos;
    }

    private function verificarTransicion(Contratacion $contratacion, Estado $nuevo): void
    {
        if (! $contratacion->puedeCambiarA($nuevo)) {
            throw new ReglaNegocioException(
                'No se puede pasar de "'.$contratacion->estado->label().'" a "'.$nuevo->label().'".'
            );
        }
    }
}
