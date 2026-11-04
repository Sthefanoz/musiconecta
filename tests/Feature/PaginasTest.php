<?php

namespace Tests\Feature;

use App\Enums\EstadoContratacion as Estado;
use App\Models\Contratacion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Recorre todas las pantallas con los datos de demostración, para cada rol. */
class PaginasTest extends TestCase
{
    use RefreshDatabase;

    public function test_todas_las_pantallas_responden(): void
    {
        $this->seed(DatabaseSeeder::class);

        $andres = User::where('email', 'andres@musiconecta.test')->first();
        $maria = User::where('email', 'maria@musiconecta.test')->first();
        $admin = User::where('email', 'admin@musiconecta.test')->first();
        $musico = $andres->musico;
        $banda = $musico->bandas()->first();
        $servicio = $musico->servicios()->first();
        $contratacion = Contratacion::deMusico($musico)->where('cliente_id', $maria->id)->first();

        // Público
        foreach (['/', '/?genero_id=1&ciudad_id=1&fecha='.now()->addDays(3)->toDateString(), '/login', '/registro', "/musicos/{$musico->id}", "/bandas/{$banda->id}"] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/musico/panel')->assertRedirect('/login');

        // Músico
        $this->actingAs($andres);
        foreach (['/musico/panel', '/musico/panel?desde=2026-01-01&hasta=2026-06-30', '/musico/perfil', '/musico/servicios', '/musico/servicios/crear',
            "/musico/servicios/{$servicio->id}/editar", '/musico/repertorio', '/musico/bandas', '/musico/bandas/crear', "/musico/bandas/{$banda->id}",
            '/musico/agenda', '/musico/solicitudes', '/musico/solicitudes?filtro=pendientes', "/contrataciones/{$contratacion->id}"] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin')->assertForbidden();
        $this->get('/mis-contrataciones')->assertForbidden();

        // Cliente
        $this->actingAs($maria);
        foreach (['/mis-contrataciones', "/servicios/{$servicio->id}/contratar", "/contrataciones/{$contratacion->id}"] as $url) {
            $this->get($url)->assertOk();
        }
        $this->getJson("/servicios/{$servicio->id}/disponibilidad?fecha=".now()->addDays(40)->toDateString().'&hora=18:00&horas=1')
            ->assertOk()->assertJson(['disponible' => true]);
        $this->get('/musico/panel')->assertForbidden();

        // Cliente paga el saldo de la contratación de ayer y el músico la marca como realizada
        $ayer = Contratacion::where('cliente_id', $maria->id)->where('estado', Estado::Confirmada->value)
            ->whereDate('fecha_evento', '<', now()->toDateString())->first();
        $this->post("/contrataciones/{$ayer->id}/saldo", ['metodo' => 'tarjeta'])->assertRedirect();
        $this->actingAs($andres)->post("/musico/solicitudes/{$ayer->id}/realizada")->assertSessionHas('ok');
        $this->assertSame(Estado::Realizada, $ayer->fresh()->estado);
        $this->assertSame(1, $ayer->repartos()->count());

        // Admin
        $this->actingAs($admin);
        foreach (['/admin', '/admin/musicos', '/admin/musicos/crear', "/admin/musicos/{$musico->id}/editar", '/admin/canciones', '/admin/ciudades',
            '/admin/catalogos/generos', '/admin/catalogos/provincias', '/admin/catalogos/tipos-servicio', '/admin/catalogos/tipos-evento'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_registro_de_cliente_y_solicitud(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post('/registro', [
            'name' => 'Nuevo Cliente', 'email' => 'nuevo@t.test', 'rol' => 'cliente',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        ])->assertRedirect('/');

        $servicio = User::where('email', 'andres@musiconecta.test')->first()->musico->servicios()->first();
        $canciones = $servicio->repertorioDisponible()->take(2)->pluck('id')->all();

        $quito = \App\Models\Ciudad::where('nombre', 'Quito')->first();
        $this->post("/servicios/{$servicio->id}/contratar", [
            'tipo_evento_id' => 2, 'provincia_id' => $quito->provincia_id, 'ciudad_id' => $quito->id, 'fecha_evento' => now()->addDays(45)->toDateString(),
            'hora_inicio' => '18:00', 'horas' => 1, 'direccion' => 'Av. Amazonas', 'canciones' => $canciones,
        ])->assertRedirect();

        $k = Contratacion::latest('id')->first();
        $this->assertSame(Estado::Solicitada, $k->estado);
        $this->assertCount(2, $k->canciones);
    }
}
