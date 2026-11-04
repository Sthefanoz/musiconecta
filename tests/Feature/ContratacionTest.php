<?php

namespace Tests\Feature;

use App\Enums\EstadoContratacion as Estado;
use App\Exceptions\ReglaNegocioException;
use App\Models\Banda;
use App\Models\Cancion;
use App\Models\Ciudad;
use App\Models\Contratacion;
use App\Models\Genero;
use App\Models\Musico;
use App\Models\Servicio;
use App\Models\TipoEvento;
use App\Models\TipoServicio;
use App\Models\User;
use App\Services\ContratacionService;
use App\Services\ReporteMusicoService;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContratacionTest extends TestCase
{
    use RefreshDatabase;

    private ContratacionService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogoSeeder::class);
        $this->svc = app(ContratacionService::class);
        Carbon::setTestNow('2026-11-02 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function musico(string $nombre, string $genero = 'Música andina'): Musico
    {
        $user = User::create(['name' => $nombre, 'email' => str($nombre)->slug().'@t.test', 'password' => 'password', 'rol' => 'musico']);

        return Musico::create([
            'user_id' => $user->id,
            'ciudad_id' => Ciudad::where('nombre', 'Quito')->value('id'),
            'genero_principal_id' => Genero::where('nombre', $genero)->value('id'),
            'cedula' => '17'.str_pad((string) (crc32($nombre) % 10000000), 7, '0', STR_PAD_LEFT).'0',
            'nombre_artistico' => $nombre,
        ]);
    }

    private function cliente(): User
    {
        return User::create(['name' => 'Cliente', 'email' => 'cli'.uniqid().'@t.test', 'password' => 'password', 'rol' => 'cliente']);
    }

    private function servicio(?Musico $m, ?Banda $b, float $precio, float $horas = 1, string $tipo = 'Serenata'): Servicio
    {
        return Servicio::create([
            'musico_id' => $m?->id, 'banda_id' => $b?->id,
            'tipo_servicio_id' => TipoServicio::where('nombre', $tipo)->value('id'),
            'nombre' => $tipo, 'precio_base' => $precio, 'duracion_horas' => $horas,
        ]);
    }

    private function trio(): array
    {
        $a = $this->musico('Ana');
        $b = $this->musico('Beto');
        $c = $this->musico('Ceci');
        $banda = Banda::create(['lider_id' => $a->id, 'genero_id' => $a->genero_principal_id, 'ciudad_id' => $a->ciudad_id, 'nombre' => 'Trío']);
        $banda->integrantes()->attach([$a->id => ['instrumento' => 'Voz', 'porcentaje_pago' => 35], $b->id => ['instrumento' => 'Guitarra', 'porcentaje_pago' => 35], $c->id => ['instrumento' => 'Bajo', 'porcentaje_pago' => 30]]);

        return [$a, $b, $c, $banda];
    }

    private function datos(string $fecha, string $hora = '18:00', float $horas = 2): array
    {
        return [
            'tipo_evento_id' => TipoEvento::where('nombre', 'Boda')->value('id'),
            'ciudad_id' => Ciudad::where('nombre', 'Quito')->value('id'),
            'fecha_evento' => $fecha, 'hora_inicio' => $hora, 'horas' => $horas, 'direccion' => 'Calle 1',
        ];
    }

    public function test_flujo_completo_genera_reparto_de_banda(): void
    {
        [$a, $b, $c, $banda] = $this->trio();
        $servicio = $this->servicio(null, $banda, 900, 2, 'Ceremonia de boda');
        $cliente = $this->cliente();

        $k = $this->svc->solicitar($cliente, $servicio, $this->datos('2026-11-14', '16:00', 2));
        $this->assertSame(Estado::Solicitada, $k->estado);
        $this->assertEquals(900, (float) $k->monto_total);
        $this->assertEquals(45, (float) $k->comision); // c = 5 %

        $this->svc->responder($k, true);
        $this->assertSame(3, $k->servicio->banda->integrantes->count());
        $this->assertDatabaseCount('bloqueos_agenda', 3); // la agenda de los 3 integrantes queda bloqueada

        $this->svc->pagarAnticipo($k->fresh(), 'tarjeta');
        $this->assertEquals(270, (float) $k->pagos()->where('tipo', 'anticipo')->value('monto')); // 30 %

        // No se puede marcar realizada sin saldo
        $this->expectExceptionOnce(fn () => $this->svc->marcarRealizada($k->fresh()));

        Carbon::setTestNow('2026-11-14 20:00:00');
        $this->svc->pagarSaldo($k->fresh(), 'efectivo');
        $this->svc->marcarRealizada($k->fresh());

        $repartos = $k->repartos()->pluck('monto_neto', 'musico_id');
        $this->assertEquals(299.25, (float) $repartos[$a->id]); // 855 × 35 %
        $this->assertEquals(299.25, (float) $repartos[$b->id]);
        $this->assertEquals(256.50, (float) $repartos[$c->id]); // 855 × 30 %
        $this->assertEquals(855, round($repartos->sum(), 2));
    }

    public function test_no_registra_la_solicitud_si_un_integrante_esta_ocupado(): void
    {
        [, $b, , $banda] = $this->trio();
        $servicio = $this->servicio(null, $banda, 900, 2);
        $b->bloqueos()->create(['inicio' => '2026-11-14 15:00', 'fin' => '2026-11-14 17:00', 'motivo' => 'Otro evento']);

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('Beto');
        $this->svc->solicitar($this->cliente(), $servicio, $this->datos('2026-11-14', '16:00', 2));
    }

    public function test_banda_con_porcentajes_que_no_suman_100_no_recibe_solicitudes(): void
    {
        [$a, , , $banda] = $this->trio();
        $banda->integrantes()->updateExistingPivot($a->id, ['porcentaje_pago' => 50]);
        $servicio = $this->servicio(null, $banda, 900, 2);

        $this->expectException(ReglaNegocioException::class);
        $this->svc->solicitar($this->cliente(), $servicio, $this->datos('2026-11-20'));
    }

    public function test_no_se_puede_saltar_estados(): void
    {
        $m = $this->musico('Solo');
        $k = $this->svc->solicitar($this->cliente(), $this->servicio($m, null, 80), $this->datos('2026-11-20', '19:00', 1));

        $this->expectException(ReglaNegocioException::class);
        $this->svc->pagarAnticipo($k, 'tarjeta'); // aún no ha sido aceptada
    }

    public function test_comparativas_del_core(): void
    {
        [$a, , , $banda] = $this->trio();
        $solo = $this->servicio($a, null, 100, 1);                  // solista: IN = 95
        $show = $this->servicio(null, $banda, 1000, 4, 'Show en vivo'); // banda: IN = 950, a Ana 35 % = 332.50
        $pasillo = Cancion::create(['titulo' => 'P1', 'genero_id' => Genero::where('nombre', 'Pasillo')->value('id')]);
        $bolero = Cancion::create(['titulo' => 'B1', 'genero_id' => Genero::where('nombre', 'Bolero')->value('id')]);
        $a->repertorio()->attach($pasillo->id);

        $crear = function (Servicio $s, string $fecha, float $horas, array $canciones, Estado $estado = Estado::Realizada) {
            $k = Contratacion::create($this->datos($fecha, '18:00', $horas) + [
                'cliente_id' => $this->cliente()->id, 'servicio_id' => $s->id,
                'monto_total' => $s->cotizar($horas), 'comision' => round($s->cotizar($horas) * 0.05, 2), 'estado' => $estado,
            ]);
            $k->canciones()->attach($canciones);
            if ($estado === Estado::Realizada) {
                $this->svc->repartir($k);
            }
        };

        $crear($solo, '2026-09-10', 1, [$pasillo->id, $bolero->id]);              // 95: 47.5 pasillo / 47.5 bolero
        $crear($solo, '2026-10-05', 1, [$pasillo->id]);                            // 95 pasillo
        $crear($show, '2026-10-12', 4, [$bolero->id, $pasillo->id]); // 332.5: mitad y mitad
        $crear($solo, '2026-10-20', 1, [], Estado::Cancelada);                      // se excluye

        $r = app(ReporteMusicoService::class)->para($a, Carbon::parse('2026-09-01'), Carbon::parse('2026-10-31'));

        $kpi = $r->indicadores();
        $this->assertEquals(427.5, $kpi['ingreso_mes']);                          // 95 + 332.5
        $this->assertEqualsWithDelta((427.5 - 95) / 95 * 100, $kpi['variacion'], 0.001);

        $formas = $r->solistaVsBanda()['formas']->keyBy('clave');
        $this->assertEquals(190, $formas['solista']['total']);
        $this->assertEquals(95, $formas['solista']['promedio']);                    // P_f
        $this->assertEquals(95, $formas['solista']['por_hora']);                    // H_f
        $this->assertEquals(332.5 / 4, $formas['banda:'.$banda->id]['por_hora']);

        $estilos = $r->repertorioSolicitado();
        $this->assertEqualsWithDelta(47.5 + 95 + 166.25, $estilos['estilos']['Pasillo'], 0.01);
        $this->assertEqualsWithDelta(47.5 + 166.25, $estilos['estilos']['Bolero'], 0.01);
        $this->assertSame('B1', $estilos['faltantes']->first()['titulo']);           // pedida pero no está en su repertorio

        $eventos = $r->ingresosPorTipoEvento();
        $this->assertSame(3, $eventos['tipos']->sum('cantidad'));

        $genero = $r->comparacionConSuGenero();
        $this->assertSame(3, $genero['n']);                                         // Ana, Beto, Ceci
        $this->assertEquals(100, $genero['posicion']);                              // gana más que los otros 2
    }

    public function test_percentil_por_interpolacion(): void
    {
        $this->assertEquals(8, ReporteMusicoService::percentil([1, 4, 7, 8, 10], 75));
        $this->assertEquals(7, ReporteMusicoService::percentil([7], 75));
        $this->assertEquals(32.5, ReporteMusicoService::percentil([10, 20, 30, 40], 75));
    }

    private function expectExceptionOnce(callable $fn): void
    {
        try {
            $fn();
            $this->fail('Se esperaba ReglaNegocioException');
        } catch (ReglaNegocioException) {
            $this->addToAssertionCount(1);
        }
    }
}
