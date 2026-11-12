<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuentas de clientes: cualquier persona se registra en la web (sin rol = cliente).
 * Los usuarios con rol (Administrador, Ventas…) son el personal y entran al panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('celular', 30)->nullable()->after('email');
            $table->string('pais', 60)->nullable()->after('celular');
        });
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', fn (Blueprint $table) => $table->dropConstrainedForeignId('user_id'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['celular', 'pais']));
    }
};
