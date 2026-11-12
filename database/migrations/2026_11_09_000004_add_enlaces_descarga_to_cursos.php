<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enlaces de descarga de cada curso (videos y materiales: WeTransfer, pCloud, Drive…).
 * Son privados: solo se ven en el panel y se ponen solos al enviar el acceso de un pedido.
 * Además, el n.º de operación del pedido admite varios números (pagó cada curso con su link).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->text('enlaces_descarga')->nullable()->after('link_pago');
        });
        // Varios números de operación separados por comas (un pago por curso con el link de Niubiz)
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('pago_operacion', 120)->change();
            $table->string('pago_operacion_norm', 120)->change();
        });
        foreach (\Illuminate\Support\Facades\DB::table('pedidos')->where('pago_operacion_norm', 'not like', ',%')->get(['id', 'pago_operacion']) as $p) {
            \Illuminate\Support\Facades\DB::table('pedidos')->where('id', $p->id)->update(['pago_operacion_norm' => \App\Models\Pedido::operacionesNorm($p->pago_operacion)]);
        }
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropColumn('enlaces_descarga');
        });
    }
};
