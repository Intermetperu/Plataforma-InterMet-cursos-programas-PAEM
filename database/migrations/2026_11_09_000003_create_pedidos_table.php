<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedidos: el cliente registra su pago (lo que en el hotel era la reserva web).
 * Estados: por_verificar → pagado (pago comprobado) → enviado (se mandó el enlace de descarga) · rechazado
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();     // C-000123
            $table->string('token', 64)->unique();      // enlace privado para ver el estado
            $table->string('estado', 20)->default('por_verificar')->index();
            // Cliente
            $table->string('nombre', 120);
            $table->string('email', 150)->index();
            $table->string('celular', 30);
            $table->string('pais', 60)->nullable();
            $table->string('documento', 30)->nullable();   // DNI, CE, pasaporte o RUC
            $table->string('empresa', 150)->nullable();
            $table->text('nota')->nullable();
            // Pago
            $table->decimal('total', 10, 2);               // USD según los precios de la web
            $table->string('pago_metodo', 20);             // niubiz · bcp · interbank · bbva · scotiabank · yape · plin · moneygram · otro
            $table->string('pago_moneda', 3)->default('USD');
            $table->decimal('pago_monto', 10, 2);
            $table->date('pago_fecha')->nullable();
            $table->string('pago_operacion', 40);
            $table->string('pago_operacion_norm', 40)->index();
            $table->string('pago_captura', 255);
            $table->string('pago_captura_hash', 64)->index();
            $table->string('pago_captura_huella', 64)->nullable();
            $table->json('pago_alertas')->nullable();
            // Atención
            $table->text('motivo_rechazo')->nullable();
            $table->text('enlace_descarga')->nullable();   // WeTransfer, Drive… (uno por línea)
            $table->text('mensaje_cliente')->nullable();
            $table->foreignId('atendido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verificado_en')->nullable();
            $table->dateTime('enviado_en')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('pedido_curso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->nullOnDelete();
            $table->unsignedSmallInteger('numero')->nullable();
            $table->string('titulo', 255);               // copia, por si el curso cambia después
            $table->decimal('precio', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_curso');
        Schema::dropIfExists('pedidos');
    }
};
