<?php

namespace Tests\Feature;

use App\Models\Ciudad;
use App\Models\Genero;
use App\Models\Musico;
use App\Models\Provincia;
use App\Models\User;
use App\Rules\CedulaEcuatoriana;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Administración que alimenta el Core: validación de la cédula y dropdown Provincia → Ciudad. */
class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogoSeeder::class);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@t.test', 'password' => 'password', 'rol' => 'admin']);
    }

    private function datos(array $cambios = []): array
    {
        $quito = Ciudad::where('nombre', 'Quito')->first();

        return array_merge([
            'name' => 'Rosa Quishpe', 'email' => 'rosa@t.test', 'password' => 'secreto123',
            'telefono' => '0991234567', 'cedula' => '1710034065',
            'nombre_artistico' => 'Rosa Q', 'anios_experiencia' => 5,
            'genero_principal_id' => Genero::where('nombre', 'Música andina')->value('id'),
            'provincia_id' => $quito->provincia_id, 'ciudad_id' => $quito->id,
        ], $cambios);
    }

    public function test_regla_cedula_modulo_10(): void
    {
        $this->assertTrue(CedulaEcuatoriana::esValida('1710034065'));
        $this->assertTrue(CedulaEcuatoriana::esValida('0926687856'));
        $this->assertFalse(CedulaEcuatoriana::esValida('1710034066'));   // dígito verificador incorrecto
        $this->assertFalse(CedulaEcuatoriana::esValida('2510034065'));   // provincia 25 no existe
        $this->assertFalse(CedulaEcuatoriana::esValida('1770034065'));   // tercer dígito ≥ 6
        $this->assertFalse(CedulaEcuatoriana::esValida('17100340'));     // longitud
        $this->assertFalse(CedulaEcuatoriana::esValida('17100340AB'));   // no numérica
    }

    public function test_admin_registra_musico_con_cedula_valida(): void
    {
        $this->actingAs($this->admin)->post('/admin/musicos', $this->datos(['cedula' => '171003406-5']))
            ->assertRedirect('/admin/musicos')->assertSessionHasNoErrors();

        $musico = Musico::where('cedula', '1710034065')->first();   // se guarda normalizada
        $this->assertNotNull($musico);
        $this->assertSame('musico', $musico->user->rol->value);
    }

    public function test_cedula_invalida_no_se_graba_aunque_se_salte_el_javascript(): void
    {
        $this->actingAs($this->admin)->post('/admin/musicos', $this->datos(['cedula' => '1710034066']))
            ->assertSessionHasErrors('cedula');

        $this->assertDatabaseCount('musicos', 0);
        $this->assertDatabaseMissing('users', ['email' => 'rosa@t.test']);
    }

    public function test_cedula_duplicada_es_rechazada(): void
    {
        $this->actingAs($this->admin)->post('/admin/musicos', $this->datos());
        $this->post('/admin/musicos', $this->datos(['email' => 'otra@t.test']))
            ->assertSessionHasErrors(['cedula' => 'Ya existe un músico registrado con esa cédula.']);

        $this->assertDatabaseCount('musicos', 1);
    }

    public function test_la_ciudad_debe_pertenecer_a_la_provincia(): void
    {
        $guayas = Provincia::where('nombre', 'Guayas')->first();

        $this->actingAs($this->admin)->post('/admin/musicos', $this->datos(['provincia_id' => $guayas->id]))  // Quito no es de Guayas
            ->assertSessionHasErrors('ciudad_id');

        $this->assertDatabaseCount('musicos', 0);
    }

    public function test_endpoint_del_dropdown_devuelve_solo_las_ciudades_de_la_provincia(): void
    {
        $pichincha = Provincia::where('nombre', 'Pichincha')->first();

        $ciudades = $this->getJson("/ubicacion/provincias/{$pichincha->id}/ciudades")->assertOk()->json();

        $this->assertEqualsCanonicalizing(['Cayambe', 'Machachi', 'Quito', 'Sangolquí'], array_column($ciudades, 'nombre'));
    }

    public function test_crud_de_ciudades_y_canciones(): void
    {
        $azuay = Provincia::where('nombre', 'Azuay')->first();
        $this->actingAs($this->admin)->post('/admin/ciudades', ['provincia_id' => $azuay->id, 'nombre' => 'Sígsig'])->assertSessionHasNoErrors();
        $this->post('/admin/ciudades', ['provincia_id' => $azuay->id, 'nombre' => 'Sígsig'])->assertSessionHasErrors('nombre');
        $this->assertDatabaseHas('ciudades', ['nombre' => 'Sígsig', 'provincia_id' => $azuay->id]);

        $this->post('/admin/canciones', ['titulo' => 'Chola cuencana', 'artista_original' => 'Tradicional', 'genero_id' => Genero::first()->id])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('canciones', ['titulo' => 'Chola cuencana']);
    }

    public function test_listado_de_ciudades_cuenta_sus_musicos(): void
    {
        $this->actingAs($this->admin)->post('/admin/musicos', $this->datos());

        $ciudades = $this->get('/admin/ciudades')->assertOk()->viewData('ciudades');
        $this->assertSame(1, $ciudades->firstWhere('nombre', 'Quito')->musicos_count);
        $this->assertSame(0, $ciudades->firstWhere('nombre', 'Cuenca')->musicos_count);
    }

    public function test_no_se_elimina_una_provincia_que_tiene_ciudades(): void
    {
        $pichincha = Provincia::where('nombre', 'Pichincha')->first();

        $this->actingAs($this->admin)->delete("/admin/catalogos/provincias/{$pichincha->id}")
            ->assertSessionHas('error');
        $this->assertDatabaseHas('provincias', ['id' => $pichincha->id]);
    }

    public function test_eliminar_musico_sin_historial(): void
    {
        $this->actingAs($this->admin)->post('/admin/musicos', $this->datos());
        $musico = Musico::where('cedula', '1710034065')->first();

        $this->delete("/admin/musicos/{$musico->id}")->assertSessionHas('ok');

        $this->assertDatabaseMissing('musicos', ['id' => $musico->id]);
        $this->assertDatabaseMissing('users', ['email' => 'rosa@t.test']);
    }

    public function test_no_se_elimina_un_musico_con_historial(): void
    {
        $this->actingAs($this->admin)->post('/admin/musicos', $this->datos());
        $musico = Musico::where('cedula', '1710034065')->first();
        \App\Models\Banda::create(['lider_id' => $musico->id, 'genero_id' => $musico->genero_principal_id, 'ciudad_id' => $musico->ciudad_id, 'nombre' => 'B']);

        $this->delete("/admin/musicos/{$musico->id}")->assertSessionHas('error');

        $this->assertDatabaseHas('musicos', ['id' => $musico->id]);
    }

    public function test_solo_el_admin_entra_a_la_administracion(): void
    {
        $cliente = User::create(['name' => 'C', 'email' => 'c@t.test', 'password' => 'password', 'rol' => 'cliente']);
        $this->actingAs($cliente)->get('/admin/musicos')->assertForbidden();
        $this->actingAs($cliente)->post('/admin/musicos', $this->datos())->assertForbidden();
    }
}
