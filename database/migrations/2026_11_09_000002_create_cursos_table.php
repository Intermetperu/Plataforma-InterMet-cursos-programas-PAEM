<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Cursos y congresos digitales (grabados) que se venden en la web */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero')->nullable()->index(); // N° del catálogo (#01, #02…)
            $table->string('slug', 190)->unique();                       // dirección: /cursos-digitales/1-optimizacion-en-plantas-de-flotacion
            $table->string('titulo', 255);
            $table->string('tipo', 20)->default('curso');                // curso · congreso
            $table->string('idioma', 2)->default('es');                  // es · en
            $table->unsignedSmallInteger('anio')->nullable()->index();   // año en que salió
            $table->date('fecha_publicacion')->nullable();               // fecha exacta (opcional)
            $table->decimal('precio', 10, 2);                            // precio regular en USD
            $table->decimal('precio_oferta', 10, 2)->nullable();         // precio con descuento
            $table->boolean('en_oferta')->default(false);                // mostrar el precio de oferta
            $table->decimal('horas', 5, 1)->nullable();                  // horas de video
            $table->string('incluye', 120)->nullable();                  // software / planillas Excel
            $table->string('instructor', 255)->nullable();
            $table->text('instructor_bio')->nullable();
            $table->text('resumen')->nullable();
            $table->text('dirigido_a')->nullable();                      // una línea por público
            $table->text('temario')->nullable();                         // un tema por línea
            $table->string('video_url', 255)->nullable();                // YouTube
            $table->string('imagen', 255)->nullable();                   // portada subida
            $table->string('link_pago', 255)->nullable();                // link de pago (Niubiz)
            $table->boolean('publicado')->default(true);
            $table->boolean('destacado')->default(false);
            $table->unsignedInteger('vendidos')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
