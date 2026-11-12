<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roles con permisos por módulo (igual que en el sistema del hotel) y ajustes
 * que se cambian desde Admin › Configuración (clave → valor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->string('descripcion', 200)->nullable();
            $table->boolean('acceso_total')->default(false); // Administrador: todo
            $table->json('permisos')->nullable();             // ["tablero","pedidos","cursos",...]
            $table->timestamps();
        });

        $ahora = now();
        foreach ([
            ['Administrador', 'Acceso a todo el sistema.', true, []],
            ['Ventas', 'Revisa los pagos, envía los accesos y ve los cursos.', false, ['tablero', 'pedidos', 'cursos']],
            ['Contenido', 'Agrega y edita cursos (textos, videos, precios y links de pago).', false, ['tablero', 'cursos']],
        ] as [$nombre, $descripcion, $total, $permisos]) {
            DB::table('roles')->insert([
                'nombre' => $nombre, 'descripcion' => $descripcion, 'acceso_total' => $total,
                'permisos' => json_encode($permisos), 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained('roles')->nullOnDelete();
            $table->boolean('activo')->default(true)->after('role_id');
        });

        Schema::create('ajustes', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->text('valor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn('activo');
        });
        Schema::dropIfExists('roles');
    }
};
