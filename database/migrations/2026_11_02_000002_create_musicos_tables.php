<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('musicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('ciudad_id')->constrained('ciudades');
            $table->foreignId('genero_principal_id')->constrained('generos');
            // Dato sensible: identifica al músico que recibe el reparto del pago. Validado en back-end (módulo 10).
            $table->string('cedula', 10)->unique();
            $table->string('nombre_artistico', 150);
            $table->text('biografia')->nullable();
            $table->unsignedInteger('anios_experiencia')->default(0);
            $table->boolean('verificado')->default(false);
            $table->timestamps();
        });

        Schema::create('musico_genero', function (Blueprint $table) {
            $table->foreignId('musico_id')->constrained('musicos')->cascadeOnDelete();
            $table->foreignId('genero_id')->constrained('generos')->cascadeOnDelete();
            $table->primary(['musico_id', 'genero_id']);
        });

        Schema::create('bandas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lider_id')->constrained('musicos')->cascadeOnDelete();
            $table->foreignId('genero_id')->constrained('generos');
            $table->foreignId('ciudad_id')->constrained('ciudades');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('banda_musico', function (Blueprint $table) {
            $table->foreignId('banda_id')->constrained('bandas')->cascadeOnDelete();
            $table->foreignId('musico_id')->constrained('musicos')->cascadeOnDelete();
            $table->string('instrumento', 80);
            $table->decimal('porcentaje_pago', 5, 2);
            $table->primary(['banda_id', 'musico_id']);
        });

        Schema::create('canciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('genero_id')->constrained('generos'); // "estilo" de la canción
            $table->string('titulo', 150);
            $table->string('artista_original', 150)->nullable();
            $table->timestamps();
        });

        // Repertorio de un músico (solista) o de una banda: solo uno de los dos FK tiene valor.
        Schema::create('repertorio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('musico_id')->nullable()->constrained('musicos')->cascadeOnDelete();
            $table->foreignId('banda_id')->nullable()->constrained('bandas')->cascadeOnDelete();
            $table->foreignId('cancion_id')->constrained('canciones')->cascadeOnDelete();
            $table->unique(['musico_id', 'banda_id', 'cancion_id']);
        });

        // Servicio ofrecido por un músico (solista) o por una banda: solo uno de los dos FK tiene valor.
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('musico_id')->nullable()->constrained('musicos')->cascadeOnDelete();
            $table->foreignId('banda_id')->nullable()->constrained('bandas')->cascadeOnDelete();
            $table->foreignId('tipo_servicio_id')->constrained('tipos_servicio');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->decimal('precio_base', 10, 2);
            $table->decimal('duracion_horas', 4, 1);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
        Schema::dropIfExists('repertorio');
        Schema::dropIfExists('canciones');
        Schema::dropIfExists('banda_musico');
        Schema::dropIfExists('bandas');
        Schema::dropIfExists('musico_genero');
        Schema::dropIfExists('musicos');
    }
};
