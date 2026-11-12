<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Temario propio del programa y galería de miniaturas subidas desde el panel */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programas', function (Blueprint $table) {
            $table->text('temario')->nullable()->after('descripcion');          // un tema por línea; «# Módulo» = título
            $table->json('galeria')->nullable()->after('imagen');               // fotos extra (miniaturas)
            $table->boolean('fotos_cursos')->default(true)->after('galeria');   // sumar las fotos de sus cursos
        });
    }

    public function down(): void
    {
        Schema::table('programas', function (Blueprint $table) {
            $table->dropColumn(['temario', 'galeria', 'fotos_cursos']);
        });
    }
};
