<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrataciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('users');
            $table->foreignId('servicio_id')->constrained('servicios');
            $table->foreignId('tipo_evento_id')->constrained('tipos_evento');
            $table->foreignId('ciudad_id')->constrained('ciudades');
            $table->date('fecha_evento');
            $table->time('hora_inicio');
            $table->decimal('horas', 4, 1);
            $table->string('direccion', 255);
            $table->decimal('monto_total', 10, 2);
            $table->decimal('comision', 10, 2);
            // solicitada | aceptada | confirmada | realizada | rechazada | cancelada
            $table->string('estado', 20)->default('solicitada');
            $table->timestamps();

            $table->index(['estado', 'fecha_evento']);
        });

        Schema::create('contratacion_cancion', function (Blueprint $table) {
            $table->foreignId('contratacion_id')->constrained('contrataciones')->cascadeOnDelete();
            $table->foreignId('cancion_id')->constrained('canciones')->cascadeOnDelete();
            $table->primary(['contratacion_id', 'cancion_id']);
        });

        Schema::create('repartos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contratacion_id')->constrained('contrataciones')->cascadeOnDelete();
            $table->foreignId('musico_id')->constrained('musicos')->cascadeOnDelete();
            $table->decimal('porcentaje', 5, 2);
            $table->decimal('monto_neto', 10, 2);
            $table->unique(['contratacion_id', 'musico_id']);
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contratacion_id')->constrained('contrataciones')->cascadeOnDelete();
            $table->string('tipo', 20); // anticipo | saldo
            $table->decimal('monto', 10, 2);
            $table->string('metodo', 30);
            $table->dateTime('pagado_en');
            $table->timestamps();
            $table->unique(['contratacion_id', 'tipo']);
        });

        Schema::create('resenas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contratacion_id')->unique()->constrained('contrataciones')->cascadeOnDelete();
            $table->unsignedTinyInteger('puntuacion');
            $table->text('comentario')->nullable();
            $table->timestamps();
        });

        Schema::create('bloqueos_agenda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('musico_id')->constrained('musicos')->cascadeOnDelete();
            $table->foreignId('contratacion_id')->nullable()->constrained('contrataciones')->cascadeOnDelete();
            $table->dateTime('inicio');
            $table->dateTime('fin');
            $table->string('motivo', 150)->nullable();
            $table->timestamps();
            $table->index(['musico_id', 'inicio', 'fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos_agenda');
        Schema::dropIfExists('resenas');
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('repartos');
        Schema::dropIfExists('contratacion_cancion');
        Schema::dropIfExists('contrataciones');
    }
};
