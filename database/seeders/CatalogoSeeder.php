<?php

namespace Database\Seeders;

use App\Models\Ciudad;
use App\Models\Genero;
use App\Models\Provincia;
use App\Models\TipoEvento;
use App\Models\TipoServicio;
use Illuminate\Database\Seeder;

class CatalogoSeeder extends Seeder
{
    public const GENEROS = ['Música andina', 'Pasillo', 'Bolero', 'Balada', 'Sanjuanito', 'Salsa', 'Rock en español', 'Pop', 'Jazz', 'Mariachi', 'Cumbia', 'Música clásica'];

    /** Provincias del Ecuador y algunas de sus ciudades. */
    public const PROVINCIAS = [
        'Azuay' => ['Cuenca', 'Gualaceo', 'Paute'],
        'Bolívar' => ['Guaranda'],
        'Cañar' => ['Azogues', 'La Troncal'],
        'Carchi' => ['Tulcán'],
        'Chimborazo' => ['Riobamba', 'Guano'],
        'Cotopaxi' => ['Latacunga', 'Salcedo'],
        'El Oro' => ['Machala', 'Pasaje'],
        'Esmeraldas' => ['Esmeraldas', 'Atacames'],
        'Galápagos' => ['Puerto Ayora', 'Puerto Baquerizo Moreno'],
        'Guayas' => ['Guayaquil', 'Durán', 'Samborondón', 'Milagro'],
        'Imbabura' => ['Ibarra', 'Otavalo', 'Cotacachi'],
        'Loja' => ['Loja', 'Catamayo'],
        'Los Ríos' => ['Babahoyo', 'Quevedo'],
        'Manabí' => ['Manta', 'Portoviejo', 'Chone'],
        'Morona Santiago' => ['Macas'],
        'Napo' => ['Tena'],
        'Orellana' => ['Francisco de Orellana'],
        'Pastaza' => ['Puyo'],
        'Pichincha' => ['Quito', 'Sangolquí', 'Cayambe', 'Machachi'],
        'Santa Elena' => ['Santa Elena', 'Salinas'],
        'Santo Domingo de los Tsáchilas' => ['Santo Domingo'],
        'Sucumbíos' => ['Nueva Loja'],
        'Tungurahua' => ['Ambato', 'Baños de Agua Santa'],
        'Zamora Chinchipe' => ['Zamora'],
    ];

    public const TIPOS_SERVICIO = ['Serenata', 'Show en vivo', 'Ceremonia de boda', 'Clase particular'];

    public const TIPOS_EVENTO = ['Boda', 'Cumpleaños', 'Corporativo', 'Bar / restaurante', 'Aniversario', 'Otro'];

    public function run(): void
    {
        foreach (self::GENEROS as $n) {
            Genero::firstOrCreate(['nombre' => $n]);
        }
        foreach (self::PROVINCIAS as $provincia => $ciudades) {
            $p = Provincia::firstOrCreate(['nombre' => $provincia]);
            foreach ($ciudades as $ciudad) {
                Ciudad::firstOrCreate(['provincia_id' => $p->id, 'nombre' => $ciudad]);
            }
        }
        foreach (self::TIPOS_SERVICIO as $n) {
            TipoServicio::firstOrCreate(['nombre' => $n]);
        }
        foreach (self::TIPOS_EVENTO as $n) {
            TipoEvento::firstOrCreate(['nombre' => $n]);
        }
    }
}
