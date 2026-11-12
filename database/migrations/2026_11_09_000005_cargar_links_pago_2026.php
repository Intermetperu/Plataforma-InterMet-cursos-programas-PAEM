<?php

use App\Support\Excel;
use App\Support\ImportadorCursos;
use Illuminate\Database\Migrations\Migration;

/**
 * Carga los links de pago 2026 (COMINSAC · Niubiz) y los enlaces de descarga de su lista
 * en una base que ya existía: basta con `php artisan migrate` (no borra pedidos ni cambios).
 */
return new class extends Migration
{
    public function up(): void
    {
        $lista = database_path('data/links-pago-2026.txt');
        if (is_file($lista) && \App\Models\Curso::count() > 0) {
            (new ImportadorCursos(false))->importar(Excel::leer($lista, 'txt'));
        }
    }

    public function down(): void
    {
        // Los datos cargados se quedan.
    }
};
