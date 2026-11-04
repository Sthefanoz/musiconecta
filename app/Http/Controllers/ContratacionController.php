<?php

namespace App\Http\Controllers;

use App\Enums\EstadoContratacion as Estado;
use App\Exceptions\ReglaNegocioException;
use App\Http\Requests\SolicitarContratacionRequest;
use App\Models\Ciudad;
use App\Models\Contratacion;
use App\Models\Servicio;
use App\Models\TipoEvento;
use App\Services\AgendaService;
use App\Services\ContratacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContratacionController extends Controller
{
    public function __construct(private ContratacionService $contrataciones, private AgendaService $agenda)
    {
    }

    /** Mis contrataciones (cliente). */
    public function index(Request $request): View
    {
        $contrataciones = $request->user()->contrataciones()
            ->with(['servicio.musico', 'servicio.banda', 'tipoEvento', 'pagos', 'resena'])
            ->orderByDesc('fecha_evento')
            ->get();

        return view('contrataciones.cliente', compact('contrataciones'));
    }

    public function create(Servicio $servicio): View
    {
        abort_unless($servicio->activo, 404);
        $servicio->load(['tipo', 'musico.ciudad', 'banda.integrantes', 'banda.ciudad']);

        $musico = $servicio->esDeBanda() ? $servicio->banda->lider : $servicio->musico;

        return view('contrataciones.create', [
            'servicio' => $servicio,
            'canciones' => $servicio->repertorioDisponible(),
            'tiposEvento' => TipoEvento::orderBy('nombre')->get(),
            'ciudades' => Ciudad::orderBy('nombre')->get(),
            'ciudadDefecto' => $servicio->esDeBanda() ? $servicio->banda->ciudad_id : $servicio->musico->ciudad_id,
            'calificacion' => $musico->calificacionPromedio(),
            'cantidadResenas' => $musico->cantidadResenas(),
            'verificado' => ! $servicio->esDeBanda() && $servicio->musico->verificado,
        ]);
    }

    /** Verificación de disponibilidad en vivo (AJAX) desde el formulario de solicitud. */
    public function disponibilidad(Request $request, Servicio $servicio): JsonResponse
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'horas' => ['required', 'numeric', 'min:0.5', 'max:12'],
        ]);

        $inicio = Carbon::parse($datos['fecha'].' '.$datos['hora']);
        $fin = $inicio->copy()->addMinutes((int) round($datos['horas'] * 60));
        $monto = $servicio->cotizar((float) $datos['horas']);

        $resultado = $inicio->isPast()
            ? ['disponible' => false, 'mensaje' => 'La fecha del evento debe ser futura.']
            : $this->agenda->mensajeDisponibilidad($servicio, $inicio, $fin);

        return response()->json($resultado + [
            'monto' => $monto,
            'anticipo' => round($monto * config('musiconecta.anticipo'), 2),
        ]);
    }

    public function store(SolicitarContratacionRequest $request, Servicio $servicio): RedirectResponse
    {
        $contratacion = $this->contrataciones->solicitar(
            $request->user(),
            $servicio,
            $request->safe()->except('canciones'),
            $request->input('canciones', []),
        );

        return redirect()->route('contrataciones.show', $contratacion)
            ->with('ok', 'Solicitud enviada. El músico tiene 48 horas para aceptar o rechazar.');
    }

    public function show(Request $request, Contratacion $contratacion): View
    {
        $contratacion->load(['cliente', 'servicio.tipo', 'servicio.musico', 'servicio.banda.integrantes', 'tipoEvento', 'ciudad', 'canciones.genero', 'pagos', 'repartos.musico', 'resena']);

        $user = $request->user();
        $musico = $user->musico;
        abort_unless(
            $user->esAdmin() || $contratacion->cliente_id === $user->id || $contratacion->participa($musico),
            403
        );

        return view('contrataciones.show', [
            'c' => $contratacion,
            'musico' => $musico,
            'esCliente' => $contratacion->cliente_id === $user->id,
            'esResponsable' => $contratacion->esResponsable($musico),
        ]);
    }

    public function pagarAnticipo(Request $request, Contratacion $contratacion): RedirectResponse
    {
        $this->soloDueno($request, $contratacion);
        $metodo = $this->metodo($request);
        $this->contrataciones->pagarAnticipo($contratacion, $metodo);

        return back()->with('ok', 'Anticipo de '.dinero($contratacion->montoAnticipo()).' pagado. La contratación quedó confirmada.');
    }

    public function pagarSaldo(Request $request, Contratacion $contratacion): RedirectResponse
    {
        $this->soloDueno($request, $contratacion);
        $metodo = $this->metodo($request);
        $this->contrataciones->pagarSaldo($contratacion, $metodo);

        return back()->with('ok', 'Saldo de '.dinero($contratacion->montoSaldo()).' pagado. ¡Gracias!');
    }

    public function cancelar(Request $request, Contratacion $contratacion): RedirectResponse
    {
        $this->soloDueno($request, $contratacion);
        $this->contrataciones->cancelar($contratacion);

        return back()->with('ok', 'La contratación fue cancelada.');
    }

    /** Una reseña por contratación, solo cuando está realizada. */
    public function resena(Request $request, Contratacion $contratacion): RedirectResponse
    {
        $this->soloDueno($request, $contratacion);
        $datos = $request->validate([
            'puntuacion' => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($contratacion->estado !== Estado::Realizada) {
            throw new ReglaNegocioException('Solo puedes reseñar contrataciones realizadas.');
        }
        if ($contratacion->resena()->exists()) {
            throw new ReglaNegocioException('Ya dejaste una reseña para esta contratación.');
        }

        $contratacion->resena()->create($datos);

        return back()->with('ok', '¡Gracias por tu reseña!');
    }

    private function soloDueno(Request $request, Contratacion $contratacion): void
    {
        abort_unless($contratacion->cliente_id === $request->user()->id, 403);
    }

    private function metodo(Request $request): string
    {
        return $request->validate([
            'metodo' => ['required', Rule::in(array_keys(config('musiconecta.metodos_pago')))],
        ])['metodo'];
    }
}
