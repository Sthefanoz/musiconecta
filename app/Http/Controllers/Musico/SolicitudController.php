<?php

namespace App\Http\Controllers\Musico;

use App\Enums\EstadoContratacion as Estado;
use App\Http\Controllers\Controller;
use App\Models\Contratacion;
use App\Services\ContratacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Solicitudes y contrataciones del músico (como solista o con sus bandas). */
class SolicitudController extends Controller
{
    public function __construct(private ContratacionService $contrataciones)
    {
    }

    public function index(Request $request): View
    {
        $musico = $request->user()->musico;
        $filtro = $request->query('filtro', 'todas');

        $base = Contratacion::query()->deMusico($musico);
        $conteoPendientes = (clone $base)->where('estado', Estado::Solicitada->value)->count();

        $contrataciones = $base
            ->with(['cliente', 'servicio.tipo', 'servicio.musico', 'servicio.banda.integrantes', 'tipoEvento', 'repartos', 'pagos'])
            ->when($filtro === 'pendientes', fn ($q) => $q->where('estado', Estado::Solicitada->value))
            ->when($filtro === 'confirmadas', fn ($q) => $q->whereIn('estado', [Estado::Aceptada->value, Estado::Confirmada->value]))
            ->when($filtro === 'realizadas', fn ($q) => $q->where('estado', Estado::Realizada->value))
            ->orderByDesc('fecha_evento')->orderByDesc('hora_inicio')
            ->paginate(25)->withQueryString();

        return view('musico.solicitudes', compact('contrataciones', 'musico', 'filtro', 'conteoPendientes'));
    }

    public function aceptar(Request $request, Contratacion $contratacion): RedirectResponse
    {
        $this->soloResponsable($request, $contratacion);
        $this->contrataciones->responder($contratacion, true);

        return back()->with('ok', 'Solicitud aceptada. Se bloqueó la agenda y el cliente ya puede pagar el anticipo.');
    }

    public function rechazar(Request $request, Contratacion $contratacion): RedirectResponse
    {
        $this->soloResponsable($request, $contratacion);
        $this->contrataciones->responder($contratacion, false);

        return back()->with('ok', 'Solicitud rechazada.');
    }

    public function realizada(Request $request, Contratacion $contratacion): RedirectResponse
    {
        $this->soloResponsable($request, $contratacion);
        $this->contrataciones->marcarRealizada($contratacion);

        return back()->with('ok', 'Contratación marcada como realizada. Se generó el reparto del pago.');
    }

    /** Solo el solista o el líder de la banda responde y marca como realizada. */
    private function soloResponsable(Request $request, Contratacion $contratacion): void
    {
        abort_unless($contratacion->esResponsable($request->user()->musico), 403, 'Solo el músico responsable (o el líder de la banda) puede hacerlo.');
    }
}
