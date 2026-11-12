<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Programas de Alta Especialización Minera (PAEM).
 * Cada programa agrupa varios cursos digitales (N° del catálogo) y tiene su propio precio y link de pago.
 * Datos del Excel «Programas de Alta Especialización Minera» (precios en USD del Excel).
 */
return new class extends Migration
{
    /** [N°, nombre, subtítulo, título completo, cursos (N° del catálogo), precio USD, color, ícono] */
    private const PROGRAMAS = [
        [1, 'Flotación', 'Nivel intermedio', 'Flotación nivel intermedio', [1, 6, 15, 18, 31, 40, 137, 149], 399, 'naranja', 'bx-water'],
        [2, 'Flotación', 'Nivel pro', 'Flotación nivel pro', [38, 61, 96, 103, 128, 150, 169], 750, 'naranja', 'bx-water'],
        [3, 'Flotación', '2026', 'Flotación 2026', [179], 750, 'naranja', 'bx-water'],
        [4, 'Molienda', 'Nivel intermedio', 'Molienda nivel intermedio', [2, 7, 22, 46], 399, 'azul', 'bx-cog'],
        [5, 'Molienda', 'Nivel pro', 'Molienda nivel pro', [25, 29, 32, 41, 45, 62, 88, 93, 104, 116, 126, 130, 136, 164], 750, 'azul', 'bx-cog'],
        [6, 'Geometalurgia', 'Integral', 'Geometalurgia', [43, 44, 90, 108], 399, 'marron', 'bx-layer'],
        [7, 'Muestreo', 'Aurífero', 'Muestreo aurífero', [98, 110, 152], 399, 'dorado', 'bx-target-lock'],
        [8, 'GoldSim', 'Simulación', 'GoldSim', [68, 117, 138], 399, 'azul', 'bx-line-chart'],
        [9, 'METSIM', 'Simulación', 'METSIM', [33, 39, 105], 399, 'azul', 'bx-sitemap'],
        [10, 'Pruebas', 'Metalúrgicas', 'Pruebas metalúrgicas', [72, 129], 399, 'verde', 'bx-test-tube'],
        [11, 'Machine', 'Learning', 'Machine learning', [70, 92, 95, 100, 174], 750, 'morado', 'bx-brain'],
        [12, 'Simulación', 'Dinámica', 'Simulación dinámica', [75, 83, 84], 399, 'morado', 'bx-pulse'],
        [13, 'Diseño', 'de plantas', 'Diseño de plantas (incluye dimensionamiento)', [3, 26, 47, 48, 53, 54, 89, 94], 750, 'azul', 'bx-buildings'],
        [14, 'Procesos', 'Auríferos', 'Procesos auríferos', [4, 36, 77, 115, 121, 127], 399, 'dorado', 'bx-diamond'],
        [15, 'Relaves', 'Nivel intermedio', 'Relaves nivel intermedio', [86, 109, 158, 165, 172], 399, 'azul', 'bx-droplet'],
        [16, 'Relaves', 'Nivel avanzado', 'Relaves nivel avanzado', [166, 175], 750, 'azul', 'bx-droplet'],
        [17, 'Cobre', 'Procesamiento', 'Cobre', [27, 63, 71, 118, 120], 399, 'naranja', 'bx-cube'],
        [18, 'Fundición', 'y refinación', 'Fundición y refinación', [23, 35], 399, 'rojo', 'bxs-hot'],
        [19, 'Mejores prácticas', 'y benchmarking', 'Mejores prácticas y benchmarking', [14, 140], 399, 'verde', 'bx-medal'],
        [20, 'Chancado', 'y zarandas', 'Chancado y zarandas', [34, 76, 106], 399, 'marron', 'bx-grid'],
        [21, 'Hidrometalurgia', 'Procesos', 'Hidrometalurgia', [8, 9, 33, 49, 52, 102, 113, 125, 161], 399, 'azul', 'bx-vial'],
        [22, 'Control de procesos', 'y transformación digital', 'Control de procesos, gemelos digitales y transformación digital', [73, 112, 123, 147], 399, 'morado', 'bx-chip'],
        [23, 'Muestreo', 'en cobre', 'Muestreo en cobre', [55, 56, 91, 114, 132, 142, 155, 157, 156, 173], 750, 'naranja', 'bx-target-lock'],
        [24, 'Mine to Mill', 'Optimización', 'Mine to Mill', [78, 81, 82, 135], 399, 'rojo', 'bx-transfer'],
        [25, 'Comisionamiento', 'y puesta en marcha', 'Comisionamiento y puesta en marcha', [12, 37, 80, 99, 111, 122], 750, 'verde', 'bx-rocket'],
        [26, 'Minería', '5.0', 'Minería 5.0', [162, 163, 170, 171], 399, 'rojo', 'bx-chip'],
        [27, 'Minería 5.0', '2026', 'Minería 5.0 - 2026', [180], 499, 'rojo', 'bx-chip'],
        [28, 'ExpoCobre', 'Conferencias', 'ExpoCobre', [144, 167, 168], 399, 'naranja', 'bx-microphone'],
        [29, 'JK SimMet', 'Nivel intermedio', 'JK SimMet nivel intermedio', [53, 119], 399, 'azul', 'bx-desktop'],
        [30, 'Tópicos', 'de geología', 'Tópicos de geología', [85, 44, 55, 56], 399, 'marron', 'bx-landscape'],
        [31, 'Tópicos', 'de mina', 'Tópicos de mina', [87, 78], 399, 'marron', 'bx-hard-hat'],
        [32, 'Transporte', 'de pulpas', 'Transporte de pulpas usando Fathom', [67, 101], 399, 'azul', 'bx-git-branch'],
        [33, 'Proyectos', 'Ingeniería', 'Proyectos', [3, 5, 10, 50, 51, 74, 97, 107], 399, 'morado', 'bx-briefcase'],
        [34, 'Ambiental', 'Ingeniería', 'Ambiental', [13, 16], 299, 'verde', 'bx-leaf'],
        [35, 'Balances', 'metalúrgicos y ajustes', 'Balances metalúrgicos y ajustes', [17, 19, 20, 28, 30, 66], 399, 'azul', 'bx-calculator'],
        [36, 'Estadística', 'de procesos', 'Estadística de procesos', [24, 57, 58, 59, 65, 69, 79, 145], 399, 'morado', 'bx-bar-chart-alt-2'],
        [37, 'Litio', 'Conceptos y experiencias', 'Litio', [124], 299, 'verde', 'bx-battery'],
        [38, 'Excelencia', 'Operacional', 'Excelencia operacional', [181], 750, 'marron', 'bx-trending-up'],
    ];

    /** Cursos nuevos del Word que aún no estaban en la plataforma (sin precio propio: se venden dentro de los programas) */
    private const CURSOS_NUEVOS = [
        [177, 'CONMINUCIÓN 2025', 2025, 'congreso'],
        [178, 'AGUAS Y RELAVES 2026', 2026, 'congreso'],
        [179, 'FLOTACIÓN 2026', 2026, 'congreso'],
        [180, 'MINERÍA 5.0 (OCTUBRE 2026)', 2026, 'congreso'],
        [181, 'EXCELENCIA OPERACIONAL MINERA (OCTUBRE 2026)', 2026, 'curso'],
    ];

    public function up(): void
    {
        Schema::create('programas', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero')->nullable();
            $table->string('slug', 190)->unique();                 // /programas/1-flotacion-nivel-intermedio
            $table->string('nombre', 60);                          // tarjeta: «Flotación»
            $table->string('subtitulo', 80)->nullable();           // tarjeta: «Nivel intermedio»
            $table->string('titulo', 190);                         // «Flotación nivel intermedio»
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2);                      // USD
            $table->string('link_pago', 255)->nullable();          // link de pago (Niubiz)
            $table->string('color', 20)->default('azul');
            $table->string('icono', 40)->default('bx-award');
            $table->string('imagen', 255)->nullable();
            $table->boolean('publicado')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('curso_programa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_id')->constrained('programas')->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->unique(['programa_id', 'curso_id']);
        });

        Schema::table('pedido_curso', function (Blueprint $table) {
            $table->foreignId('programa_id')->nullable()->after('curso_id')->constrained('programas')->nullOnDelete();
        });

        $ahora = now();

        // Cursos 177–181
        foreach (self::CURSOS_NUEVOS as [$n, $titulo, $anio, $tipo]) {
            if (DB::table('cursos')->where('numero', $n)->exists()) {
                continue;
            }
            DB::table('cursos')->insert([
                'numero' => $n, 'slug' => $n . '-' . Str::slug($titulo), 'titulo' => $titulo, 'tipo' => $tipo, 'idioma' => 'es',
                'anio' => $anio, 'precio' => 0, 'publicado' => false, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        // Un curso por N° (la versión en español si hay dos idiomas)
        $porNumero = DB::table('cursos')->whereNotNull('numero')->orderByRaw("idioma = 'es' desc")->orderBy('id')->get(['id', 'numero'])
            ->unique('numero')->pluck('id', 'numero');

        foreach (self::PROGRAMAS as $i => [$n, $nombre, $sub, $titulo, $cursos, $precio, $color, $icono]) {
            $id = DB::table('programas')->insertGetId([
                'numero' => $n, 'slug' => $n . '-' . Str::slug($titulo), 'nombre' => $nombre, 'subtitulo' => $sub, 'titulo' => $titulo,
                'precio' => $precio, 'color' => $color, 'icono' => $icono, 'publicado' => true, 'orden' => $i + 1,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
            $orden = 0;
            foreach (array_unique($cursos) as $num) {
                if ($cursoId = $porNumero[$num] ?? null) {
                    DB::table('curso_programa')->insertOrIgnore(['programa_id' => $id, 'curso_id' => $cursoId, 'orden' => ++$orden]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('pedido_curso', function (Blueprint $table) {
            $table->dropConstrainedForeignId('programa_id');
        });
        Schema::dropIfExists('curso_programa');
        Schema::dropIfExists('programas');
        DB::table('cursos')->whereIn('numero', array_column(self::CURSOS_NUEVOS, 0))->where('precio', 0)->delete();
    }
};
