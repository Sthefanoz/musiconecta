<?php

namespace Database\Seeders;

use App\Enums\EstadoContratacion as Estado;
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
use App\Services\AgendaService;
use App\Services\ContratacionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos de demostración: ~14 meses de historial para que las comparativas del Core tengan contenido.
 * Cuentas: admin@ / andres@ / maria@musiconecta.test — contraseña "password".
 */
class DemoSeeder extends Seeder
{
    private array $generos;

    private array $ciudades;

    private array $tiposServicio;

    private array $tiposEvento;

    private array $canciones = [];

    /** @var User[] */
    private array $clientes = [];

    private array $repertorioCache = [];

    private ContratacionService $svc;

    public function run(): void
    {
        mt_srand(2026);
        $this->svc = app(ContratacionService::class);

        $this->generos = Genero::pluck('id', 'nombre')->all();
        $this->ciudades = Ciudad::pluck('id', 'nombre')->all();
        $this->tiposServicio = TipoServicio::pluck('id', 'nombre')->all();
        $this->tiposEvento = TipoEvento::pluck('id', 'nombre')->all();

        DB::transaction(fn () => $this->sembrar());
    }

    private function sembrar(): void
    {
        /* ---------- Usuarios base ---------- */
        User::create(['name' => 'Administrador', 'email' => 'admin@musiconecta.test', 'password' => 'password', 'rol' => 'admin']);
        $maria = User::create(['name' => 'María Torres', 'email' => 'maria@musiconecta.test', 'password' => 'password', 'rol' => 'cliente', 'telefono' => '0991234567']);
        $this->clientes[] = $maria;
        foreach (['Carlos Mena', 'Lucía Paredes', 'Daniel Ruiz', 'Ana Salazar', 'Bar La Ronda', 'Empresa Andina S.A.', 'Gabriela Andrade', 'Fernando Castillo',
            'Sofía Herrera', 'Mateo Villacís', 'Valentina Ortiz', 'Hotel Colonial', 'Paula Cevallos', 'Ricardo Benítez', 'Camila Játiva', 'Restaurante El Ventanal'] as $nombre) {
            $this->clientes[] = User::create([
                'name' => $nombre,
                'email' => $this->slug($nombre).'@cliente.test',
                'password' => 'password',
                'rol' => 'cliente',
                'telefono' => '09'.mt_rand(10000000, 99999999),
            ]);
        }

        /* ---------- Canciones (cada una con su estilo) ---------- */
        $lista = [
            ['Vasija de barro', 'Benítez y Valencia', 'Pasillo'], ['Sombras', 'Carlota Jaramillo', 'Pasillo'], ['El aguacate', 'Julio Jaramillo', 'Pasillo'],
            ['Ángel de luz', 'Benítez y Valencia', 'Pasillo'], ['Guayaquil de mis amores', 'Nicasio Safadi', 'Pasillo'], ['Romance de mi destino', 'Julio Jaramillo', 'Pasillo'],
            ['Bésame mucho', 'Consuelo Velázquez', 'Bolero'], ['Perfidia', 'Alberto Domínguez', 'Bolero'], ['Cuando calienta el sol', 'Los Hermanos Rigual', 'Bolero'],
            ['Sabor a mí', 'Álvaro Carrillo', 'Bolero'], ['Contigo aprendí', 'Armando Manzanero', 'Bolero'], ['Somos novios', 'Armando Manzanero', 'Bolero'],
            ['El cóndor pasa', 'Daniel Alomía Robles', 'Música andina'], ['Ojos azules', 'Tradicional', 'Música andina'], ['Llaqui llaqui', 'Tradicional', 'Música andina'],
            ['Toro barroso', 'Tradicional', 'Sanjuanito'], ['Pobre corazón', 'Tradicional', 'Sanjuanito'], ['Esta guitarra vieja', 'Tradicional', 'Sanjuanito'],
            ['Vivir mi vida', 'Marc Anthony', 'Salsa'], ['Yo no sé mañana', 'Luis Enrique', 'Salsa'], ['Idilio', 'Willie Colón', 'Salsa'],
            ['Lamento boliviano', 'Enanitos Verdes', 'Rock en español'], ['De música ligera', 'Soda Stereo', 'Rock en español'], ['Persiana americana', 'Soda Stereo', 'Rock en español'],
            ['Te amo', 'Franco de Vita', 'Balada'], ['Sin ti', 'Los Panchos', 'Balada'], ['Hoy tengo ganas de ti', 'Miguel Gallardo', 'Balada'],
            ['El rey', 'José Alfredo Jiménez', 'Mariachi'], ['Cielito lindo', 'Quirino Mendoza', 'Mariachi'], ['Hermoso cariño', 'Vicente Fernández', 'Mariachi'],
            ['La pollera colorá', 'Wilson Choperena', 'Cumbia'], ['Cariñito', 'Los Hijos del Sol', 'Cumbia'],
            ['Fly me to the moon', 'Bart Howard', 'Jazz'], ['Autumn leaves', 'Joseph Kosma', 'Jazz'],
        ];
        foreach ($lista as [$titulo, $artista, $estilo]) {
            $this->canciones[$titulo] = Cancion::create(['titulo' => $titulo, 'artista_original' => $artista, 'genero_id' => $this->generos[$estilo]]);
        }

        /* ---------- Andrés Vega (cuenta principal de la demo) ---------- */
        $andres = $this->musico('Andrés Vega', 'andres@musiconecta.test', 'Música andina', 'Quito', 12, true, ['Pasillo', 'Bolero', 'Sanjuanito'],
            'Guitarrista y cantante quiteño. Doce años llevando pasillos, boleros y música andina a serenatas, bodas y escenarios del país.');
        $this->repertorio($andres, ['Vasija de barro', 'Bésame mucho', 'Sombras', 'El aguacate', 'Perfidia', 'Cuando calienta el sol', 'Ángel de luz',
            'El cóndor pasa', 'Ojos azules', 'Toro barroso', 'Contigo aprendí', 'Sabor a mí', 'Romance de mi destino']);
        $andresSerenata = $this->servicio($andres, null, 'Serenata', 'Serenata', 80, 1, 'Serenata con guitarra y voz: pasillos, boleros y lo que pidas.');
        $andresClase = $this->servicio($andres, null, 'Clase particular', 'Clase de guitarra', 25, 1, 'Clases personalizadas de guitarra para todos los niveles.');

        // Compañeros de banda
        $diego = $this->musico('Diego Paredes', null, 'Música andina', 'Quito', 15, true, ['Sanjuanito']);
        $luis = $this->musico('Luis Cabrera', null, 'Música andina', 'Quito', 8, false, ['Salsa']);
        $carla = $this->musico('Carla Montalvo', null, 'Música andina', 'Quito', 6, true, ['Balada']);
        $jorge = $this->musico('Jorge Almeida', null, 'Pasillo', 'Quito', 20, true, ['Bolero', 'Música andina']);
        $pablo = $this->musico('Pablo Guerrero', null, 'Bolero', 'Quito', 9, false, ['Pasillo']);

        $andinos = $this->banda('Los Andinos', $diego, 'Música andina', 'Quito', [
            [$diego, 'Charango', 25], [$andres, 'Guitarra y voz', 25], [$luis, 'Quena y zampoña', 25], [$carla, 'Bombo y percusión', 25],
        ], 'Cuarteto de música andina contemporánea para bares, empresas y celebraciones.');
        $this->repertorio($andinos, ['El cóndor pasa', 'Ojos azules', 'Llaqui llaqui', 'Toro barroso', 'Pobre corazón', 'Esta guitarra vieja',
            'Vivir mi vida', 'Lamento boliviano', 'Vasija de barro', 'Sombras', 'Yo no sé mañana']);
        $andinosShow = $this->servicio(null, $andinos, 'Show en vivo', 'Show en vivo', 1050, 3, 'Show de tres horas con música andina y fusiones.');

        $trio = $this->banda('Trío Quiteño', $andres, 'Pasillo', 'Quito', [
            [$andres, 'Guitarra y voz', 35], [$jorge, 'Requinto', 35], [$pablo, 'Voz y maracas', 30],
        ], 'Trío clásico de pasillos y boleros para ceremonias y serenatas.');
        $this->repertorio($trio, ['Vasija de barro', 'Bésame mucho', 'Sombras', 'El aguacate', 'Perfidia', 'Cuando calienta el sol', 'Ángel de luz',
            'Sabor a mí', 'Contigo aprendí', 'Somos novios', 'Guayaquil de mis amores', 'Te amo']);
        $trioBoda = $this->servicio(null, $trio, 'Ceremonia de boda', 'Ceremonia de boda', 900, 2, 'Música para la ceremonia y el brindis.');
        $trioSerenata = $this->servicio(null, $trio, 'Serenata', 'Serenata con trío', 240, 1, 'Serenata clásica con requinto.');

        // Servicios propios de los compañeros
        $extra = [];
        $extra[] = [$this->servicio($diego, null, 'Show en vivo', 'Show andino', 420, 2), 0.8];
        $extra[] = [$this->servicio($jorge, null, 'Serenata', 'Serenata de pasillos', 110, 1), 1.1];
        $extra[] = [$this->servicio($carla, null, 'Clase particular', 'Clase de percusión', 30, 1), 0.7];
        $extra[] = [$this->servicio($luis, null, 'Serenata', 'Serenata andina', 70, 1), 0.6];
        $extra[] = [$this->servicio($pablo, null, 'Serenata', 'Serenata de boleros', 90, 1), 0.9];

        /* ---------- Otros músicos de música andina en Quito (grupo de comparación) ---------- */
        $pares = ['Rosa Quishpe', 'Marco Tipán', 'Elena Chicaiza', 'Santiago Lema', 'Nelly Guamán', 'Iván Pilataxi', 'Karina Toapanta', 'Wilson Cuzco',
            'Patricia Simbaña', 'Hernán Ushiña', 'Mónica Pazmiño', 'Raúl Gualotuña', 'Verónica Collaguazo', 'Edwin Farinango', 'Gloria Yánez',
            'Fausto Caiza', 'Lorena Morocho', 'Oswaldo Quilumba'];
        foreach ($pares as $i => $nombre) {
            $m = $this->musico($nombre, null, 'Música andina', 'Quito', mt_rand(2, 25), mt_rand(0, 1) === 1, ['Sanjuanito', 'Pasillo']);
            $this->repertorio($m, $this->azar(['Vasija de barro', 'Sombras', 'El cóndor pasa', 'Ojos azules', 'Llaqui llaqui', 'Toro barroso', 'Pobre corazón',
                'Esta guitarra vieja', 'El aguacate', 'Ángel de luz', 'Bésame mucho'], mt_rand(5, 9)));
            $factor = [0.4, 0.7, 0.9, 1.1, 1.4, 1.7, 2.0, 2.4, 2.9, 3.4][$i % 10];
            $extra[] = [$this->servicio($m, null, 'Serenata', 'Serenata', mt_rand(6, 12) * 10, 1), $factor];
            $extra[] = [$this->servicio($m, null, 'Show en vivo', 'Show en vivo', mt_rand(30, 70) * 10, mt_rand(2, 3)), $i % 2 === 0 ? $factor : $factor * 0.5];
            if ($i % 3 === 0) {
                $extra[] = [$this->servicio($m, null, 'Clase particular', 'Clase de música', mt_rand(15, 30), 1), $factor];
            }
        }

        /* ---------- Músicos de otros géneros y ciudades ---------- */
        $otros = [
            ['Salsa Brava', 'Salsa', 'Guayaquil', ['Vivir mi vida', 'Yo no sé mañana', 'Idilio']],
            ['Nicolás Rivas', 'Rock en español', 'Cuenca', ['Lamento boliviano', 'De música ligera', 'Persiana americana']],
            ['Mariachi Los Reyes', 'Mariachi', 'Quito', ['El rey', 'Cielito lindo', 'Hermoso cariño']],
            ['Daniela Ponce', 'Balada', 'Guayaquil', ['Te amo', 'Sin ti', 'Hoy tengo ganas de ti']],
            ['Quinteto Jazz Loja', 'Jazz', 'Loja', ['Fly me to the moon', 'Autumn leaves']],
            ['Los Cumbiancheros', 'Cumbia', 'Ambato', ['La pollera colorá', 'Cariñito']],
            ['Julio Andrade', 'Pasillo', 'Cuenca', ['Vasija de barro', 'Guayaquil de mis amores', 'Romance de mi destino', 'El aguacate']],
            ['Fernanda Mera', 'Bolero', 'Manta', ['Bésame mucho', 'Sabor a mí', 'Somos novios', 'Perfidia']],
        ];
        foreach ($otros as [$nombre, $genero, $ciudad, $canciones]) {
            $m = $this->musico($nombre, null, $genero, $ciudad, mt_rand(3, 18), mt_rand(0, 1) === 1, []);
            $this->repertorio($m, $canciones);
            $extra[] = [$this->servicio($m, null, 'Show en vivo', 'Show en vivo', mt_rand(35, 90) * 10, 2), 0.8];
            $extra[] = [$this->servicio($m, null, 'Serenata', 'Serenata', mt_rand(7, 15) * 10, 1), 0.8];
        }

        /* ---------- Historial: los 14 meses completos anteriores al mes actual ---------- */
        $planes = [
            // [servicio, mín, máx por mes, tipos de evento (ponderados), horas posibles]
            [$andresSerenata, 2, 6, ['Cumpleaños', 'Cumpleaños', 'Cumpleaños', 'Aniversario', 'Aniversario', 'Boda'], [1, 1, 1, 1.5]],
            [$andresClase, 1, 4, ['Otro'], [1, 2, 2]],
            [$andinosShow, 1, 3, ['Bar / restaurante', 'Bar / restaurante', 'Corporativo', 'Corporativo', 'Cumpleaños'], [3]],
            [$trioBoda, 0, 2, ['Boda'], [2]],
            [$trioSerenata, 0, 2, ['Aniversario', 'Cumpleaños'], [1]],
        ];
        foreach ($extra as [$servicio, $factor]) {
            $tipo = $servicio->tipo->nombre;
            $planes[] = match ($tipo) {
                'Serenata' => [$servicio, 0, (int) round(5 * $factor), ['Cumpleaños', 'Aniversario', 'Boda'], [1]],
                'Show en vivo' => [$servicio, 0, max(1, (int) round(3 * $factor)), ['Bar / restaurante', 'Corporativo', 'Cumpleaños'], [$servicio->duracion_horas]],
                default => [$servicio, 0, (int) round(4 * $factor), ['Otro'], [1, 2]],
            };
        }

        $mes = now()->startOfMonth()->subMonths(14);
        $limite = now()->startOfMonth()->subDay();
        while ($mes->lte($limite)) {
            $temporada = in_array($mes->month, [12, 5, 6]) ? 1 : 0; // diciembre y temporada de bodas
            foreach ($planes as [$servicio, $min, $max, $eventos, $horas]) {
                $n = mt_rand($min, $max + $temporada);
                for ($i = 0; $i < $n; $i++) {
                    $fecha = $mes->copy()->day(mt_rand(1, $mes->daysInMonth))->setTime([12, 15, 17, 19, 20, 21][mt_rand(0, 5)], [0, 30][mt_rand(0, 1)]);
                    $this->realizada($servicio, $fecha, $eventos[array_rand($eventos)], (float) $horas[array_rand($horas)]);
                }
            }
            $mes->addMonth();
        }

        /* ---------- Contrataciones en curso (para probar el flujo) ---------- */
        $agenda = app(AgendaService::class);
        $carlos = $this->clientes[1];
        $lucia = $this->clientes[2];
        $ana = $this->clientes[4];
        $bar = $this->clientes[5];

        // Por responder
        $this->svc->solicitar($maria, $trioBoda, $this->datos($this->dia(12), '16:00', 2, 'Boda', 'Hacienda La Carriona, Sangolquí'),
            $this->ids(['Vasija de barro', 'Bésame mucho', 'Sombras', 'Cuando calienta el sol']));
        $this->svc->solicitar($carlos, $andresSerenata, $this->datos($this->dia(6), '20:00', 1, 'Cumpleaños', 'Av. República de El Salvador N34-120'),
            $this->ids(['Sabor a mí', 'El aguacate']));

        // Aceptada: María debe pagar el anticipo
        $c = $this->svc->solicitar($maria, $andresSerenata, $this->datos($this->dia(26), '19:00', 1, 'Aniversario', 'Cumbayá, calle Francisco de Orellana'),
            $this->ids(['Contigo aprendí', 'Perfidia']));
        $this->svc->responder($c, true);

        // Confirmada a futuro
        $c = $this->svc->solicitar($bar, $andinosShow, $this->datos($this->dia(18), '21:00', 3, 'Bar / restaurante', 'Bar La Ronda, La Floresta'),
            $this->ids(['El cóndor pasa', 'Vivir mi vida', 'Lamento boliviano']));
        $this->svc->responder($c, true);
        $this->svc->pagarAnticipo($c, 'transferencia');

        $c = $this->svc->solicitar($lucia, $andresSerenata, $this->datos($this->dia(22), '19:30', 1, 'Aniversario', 'Conocoto, barrio La Moya'),
            $this->ids(['Bésame mucho']));
        $this->svc->responder($c, true);
        $this->svc->pagarAnticipo($c, 'tarjeta');

        // Confirmada ya ocurrida (ayer): María puede pagar el saldo y Andrés marcarla como realizada
        $c = Contratacion::create([
            'cliente_id' => $maria->id, 'servicio_id' => $andresSerenata->id, 'tipo_evento_id' => $this->tiposEvento['Cumpleaños'],
            'ciudad_id' => $this->ciudades['Quito'], 'fecha_evento' => $this->dia(-1), 'hora_inicio' => '19:30', 'horas' => 1,
            'direccion' => 'La Carolina, calle Japón', 'monto_total' => 80, 'comision' => 4, 'estado' => Estado::Confirmada,
        ]);
        $c->canciones()->attach($this->ids(['Vasija de barro', 'Sombras']));
        $c->pagos()->create(['tipo' => 'anticipo', 'monto' => $c->montoAnticipo(), 'metodo' => 'tarjeta', 'pagado_en' => now()->subDays(12)]);
        $agenda->bloquear($c);

        // Cancelada y rechazada
        $c = $this->svc->solicitar($ana, $andresSerenata, $this->datos($this->dia(33), '17:00', 1, 'Cumpleaños', 'Tumbaco'), []);
        $this->svc->cancelar($c);
        $c = $this->svc->solicitar($this->clientes[7], $andresClase, $this->datos($this->dia(8), '10:00', 2, 'Otro', 'Online'), []);
        $this->svc->responder($c, false);

        // Bloqueo manual
        $andres->bloqueos()->create(['inicio' => $this->dia(9).' 00:00:00', 'fin' => $this->dia(9).' 23:59:59', 'motivo' => 'Viaje a Cuenca']);
    }

    /* ======================= helpers ======================= */

    private function musico(string $nombre, ?string $email, string $genero, string $ciudad, int $anios, bool $verificado, array $otrosGeneros, ?string $bio = null): Musico
    {
        $user = User::create([
            'name' => $nombre,
            'email' => $email ?? $this->slug($nombre).'@musico.test',
            'password' => 'password',
            'rol' => 'musico',
            'telefono' => '09'.mt_rand(10000000, 99999999),
        ]);

        $musico = Musico::create([
            'user_id' => $user->id,
            'ciudad_id' => $this->ciudades[$ciudad],
            'genero_principal_id' => $this->generos[$genero],
            'cedula' => $this->cedula(),
            'nombre_artistico' => $nombre,
            'biografia' => $bio ?? "Músico de {$genero} en {$ciudad} con {$anios} años de experiencia.",
            'anios_experiencia' => $anios,
            'verificado' => $verificado,
        ]);
        $musico->generos()->sync(array_unique(array_map(fn ($g) => $this->generos[$g], [$genero, ...$otrosGeneros])));

        return $musico;
    }

    private function banda(string $nombre, Musico $lider, string $genero, string $ciudad, array $integrantes, string $descripcion): Banda
    {
        $banda = Banda::create([
            'lider_id' => $lider->id,
            'genero_id' => $this->generos[$genero],
            'ciudad_id' => $this->ciudades[$ciudad],
            'nombre' => $nombre,
            'descripcion' => $descripcion,
        ]);
        foreach ($integrantes as [$musico, $instrumento, $porcentaje]) {
            $banda->integrantes()->attach($musico->id, ['instrumento' => $instrumento, 'porcentaje_pago' => $porcentaje]);
        }

        return $banda;
    }

    private function servicio(?Musico $musico, ?Banda $banda, string $tipo, string $nombre, float $precio, float $horas, ?string $descripcion = null): Servicio
    {
        $servicio = Servicio::create([
            'musico_id' => $musico?->id,
            'banda_id' => $banda?->id,
            'tipo_servicio_id' => $this->tiposServicio[$tipo],
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio_base' => $precio,
            'duracion_horas' => $horas,
            'activo' => true,
        ]);

        return $servicio->load(['tipo', 'musico', 'banda.integrantes']);
    }

    private function repertorio(Musico|Banda $duenio, array $titulos): void
    {
        $duenio->repertorio()->syncWithoutDetaching($this->ids($titulos));
    }

    private function ids(array $titulos): array
    {
        return array_map(fn ($t) => $this->canciones[$t]->id, $titulos);
    }

    private function azar(array $lista, int $n): array
    {
        shuffle($lista);

        return array_slice($lista, 0, $n);
    }

    private function datos(string $fecha, string $hora, float $horas, string $evento, string $direccion): array
    {
        return [
            'tipo_evento_id' => $this->tiposEvento[$evento],
            'ciudad_id' => $this->ciudades['Quito'],
            'fecha_evento' => $fecha,
            'hora_inicio' => $hora,
            'horas' => $horas,
            'direccion' => $direccion,
        ];
    }

    /** Crea una contratación realizada con sus canciones, pagos, reparto y (a veces) reseña. */
    private function realizada(Servicio $servicio, \Carbon\CarbonInterface $fecha, string $evento, float $horas): void
    {
        $monto = $servicio->cotizar($horas);
        $cliente = $this->clientes[mt_rand(0, count($this->clientes) - 1)];

        $c = Contratacion::create([
            'cliente_id' => $cliente->id,
            'servicio_id' => $servicio->id,
            'tipo_evento_id' => $this->tiposEvento[$evento],
            'ciudad_id' => $servicio->esDeBanda() ? $servicio->banda->ciudad_id : $servicio->musico->ciudad_id,
            'fecha_evento' => $fecha->toDateString(),
            'hora_inicio' => $fecha->format('H:i'),
            'horas' => $horas,
            'direccion' => 'Dirección del evento',
            'monto_total' => $monto,
            'comision' => round($monto * config('musiconecta.comision'), 2),
            'estado' => Estado::Realizada,
        ]);
        $c->setRelation('servicio', $servicio);

        // Canciones: las primeras del repertorio son las más pedidas (sesgo cuadrático).
        if ($servicio->tipo->nombre !== 'Clase particular') {
            $repertorio = $this->repertorioCache[$servicio->id] ??= $this->repertorioOrdenado($servicio);
            if ($repertorio->isNotEmpty()) {
                $elegidas = [];
                $cuantas = mt_rand(2, min(5, $repertorio->count()));
                for ($i = 0; $i < $cuantas * 3 && count($elegidas) < $cuantas; $i++) {
                    $idx = (int) floor(pow(mt_rand() / mt_getrandmax(), 2) * $repertorio->count());
                    $elegidas[$repertorio[$idx]] = true;
                }
                $c->canciones()->attach(array_keys($elegidas));
            }
        }

        $c->pagos()->createMany([
            ['tipo' => 'anticipo', 'monto' => $c->montoAnticipo(), 'metodo' => 'tarjeta', 'pagado_en' => $fecha->copy()->subDays(mt_rand(5, 25))],
            ['tipo' => 'saldo', 'monto' => $c->montoSaldo(), 'metodo' => ['tarjeta', 'transferencia', 'efectivo'][mt_rand(0, 2)], 'pagado_en' => $fecha->copy()->addHours(3)],
        ]);

        $this->svc->repartir($c);

        if (mt_rand(1, 100) <= 70) {
            $puntos = [5, 5, 5, 5, 4, 4, 4, 3][mt_rand(0, 7)];
            $comentarios = [5 => ['¡Excelente! Todos quedaron encantados.', 'Muy puntuales y profesionales.', 'Superó nuestras expectativas.'],
                4 => ['Muy buen show, lo recomiendo.', 'Gran repertorio, llegó un poco tarde.'], 3 => ['Cumplió, aunque el sonido pudo ser mejor.']];
            $c->resena()->create(['puntuacion' => $puntos, 'comentario' => $comentarios[$puntos][array_rand($comentarios[$puntos])]]);
        }
    }

    /** Repertorio con un orden fijo de popularidad (Vasija de barro y Bésame mucho primero si están). */
    private function repertorioOrdenado(Servicio $servicio): Collection
    {
        $populares = ['Vasija de barro', 'Bésame mucho', 'Sombras', 'Vivir mi vida', 'El aguacate', 'Perfidia', 'Cuando calienta el sol', 'El cóndor pasa'];
        $ids = $servicio->repertorioDisponible()->pluck('titulo', 'id');

        return $ids->sortBy(fn ($titulo) => ($p = array_search($titulo, $populares)) === false ? 100 + mt_rand(0, 50) : $p)
            ->keys()->values();
    }

    /** Cédula ecuatoriana válida (provincia 17 = Pichincha) con su dígito verificador módulo 10. */
    private function cedula(): string
    {
        do {
            $nueve = '17'.mt_rand(0, 5).str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $cedula = $nueve.\App\Rules\CedulaEcuatoriana::digitoVerificador($nueve);
        } while (Musico::where('cedula', $cedula)->exists());

        return $cedula;
    }

    /** Fecha (Y-m-d) relativa a hoy. */
    private function dia(int $dias): string
    {
        return now()->addDays($dias)->toDateString();
    }

    private function slug(string $texto): string
    {
        return str($texto)->ascii()->lower()->replace(['.', ' '], ['', '.'])->replace('..', '.')->value();
    }
}
