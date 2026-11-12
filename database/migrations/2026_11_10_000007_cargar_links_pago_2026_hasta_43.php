<?php

use App\Support\Excel;
use App\Support\ImportadorCursos;
use Illuminate\Database\Migrations\Migration;

/**
 * Links de pago 2026 (COMINSAC · Niubiz) de los cursos #20 al #43, tomados de la hoja 1 de su Google Sheets.
 * Basta con `php artisan migrate`: solo pone los links, no cambia precios, expositores ni pedidos.
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
        // Los links cargados se quedan.
    }
};
