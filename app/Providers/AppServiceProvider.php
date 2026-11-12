<?php

namespace App\Providers;

use App\Models\Ajuste;
use App\Models\Pedido;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Fechas y mensajes en español
        Carbon::setLocale('es');
        app()->setLocale('es');
        Paginator::useBootstrapFive();

        // Datos guardados desde Admin › Configuración
        Ajuste::cargarEnConfig();

        // Límites contra abuso, cada uno con su propio contador
        RateLimiter::for('registrar-pago', fn ($r) => Limit::perMinutes(10, 6)->by($r->ip()));
        RateLimiter::for('registro', fn ($r) => Limit::perHour(10)->by($r->ip()));
        RateLimiter::for('chatbot', fn ($r) => [Limit::perMinute(20)->by($r->ip()), Limit::perHour(200)->by($r->ip())]);
        RateLimiter::for('consultar-pedido', fn ($r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('ingreso', fn ($r) => [
            Limit::perMinute(8)->by(mb_strtolower((string) $r->input('email')) . '|' . $r->ip()),
            Limit::perMinute(60)->by($r->ip()),
        ]);

        // @usd($monto) -> USD 1,250 · @soles($monto) -> S/ 1,234.50
        Blade::directive('usd', fn (string $m) => "<?php echo 'USD ' . (fmod((float) ($m), 1) ? number_format((float) ($m), 2) : number_format((float) ($m))); ?>");
        Blade::directive('soles', fn (string $m) => "<?php echo 'S/ ' . number_format((float) ($m), 2); ?>");

        // Pedidos por verificar: contador del menú y la campana del panel
        View::composer(['partials.admin.sidebar', 'partials.admin.topbar'], function ($view) {
            static $porVerificar = null;
            $porVerificar ??= Pedido::where('estado', 'por_verificar')->count();
            $view->with('porVerificar', $porVerificar);
        });
    }
}
