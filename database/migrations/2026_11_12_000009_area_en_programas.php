<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Área de cada programa, para filtrarlos en la página «Programas de alta especialización» */
return new class extends Migration
{
    private const AREAS = [
        'flotacion' => [1, 2, 3],
        'molienda' => [4, 5, 20, 24, 29],
        'oro' => [14, 18],
        'hidro' => [21, 37],
        'cobre' => [17, 28],
        'relaves' => [15, 16, 34],
        'muestreo' => [6, 7, 23, 30, 31],
        'simulacion' => [8, 9, 12, 32],
        'digital' => [11, 22, 26, 27, 36],
        'operacion' => [10, 13, 19, 25, 33, 35, 38],
    ];

    public function up(): void
    {
        Schema::table('programas', function (Blueprint $table) {
            $table->string('area', 20)->nullable()->after('subtitulo')->index();
        });
        foreach (self::AREAS as $area => $numeros) {
            DB::table('programas')->whereIn('numero', $numeros)->update(['area' => $area]);
        }
    }

    public function down(): void
    {
        Schema::table('programas', function (Blueprint $table) {
            $table->dropColumn('area');
        });
    }
};
