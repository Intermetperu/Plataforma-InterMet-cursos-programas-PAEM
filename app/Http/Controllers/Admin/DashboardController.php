<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Pedido;
use App\Models\PedidoCurso;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $vendidos = fn () => Pedido::whereIn('estado', ['pagado', 'enviado']);
        $inicioMes = now()->startOfMonth();

        // Ventas de los últimos 6 meses (USD, según el precio de los cursos)
        $meses = collect(range(5, 0))->map(function ($i) use ($vendidos) {
            $mes = now()->startOfMonth()->subMonths($i);

            return [
                'mes' => $mes->translatedFormat('M'),
                'total' => (float) $vendidos()->whereBetween('verificado_en', [$mes, $mes->copy()->endOfMonth()])->sum('total'),
            ];
        });

        return view('admin.dashboard', [
            'porVerificarLista' => Pedido::where('estado', 'por_verificar')->with('items')->oldest()->limit(6)->get(),
            'porVerificar' => Pedido::where('estado', 'por_verificar')->count(),
            'porEnviar' => Pedido::where('estado', 'pagado')->count(),
            'ventasMes' => (float) $vendidos()->where('verificado_en', '>=', $inicioMes)->sum('total'),
            'pedidosMes' => $vendidos()->where('verificado_en', '>=', $inicioMes)->count(),
            'ventasTotal' => (float) $vendidos()->sum('total'),
            'cursosPublicados' => Curso::publicados()->count(),
            'cursosSinLink' => Curso::publicados()->whereNull('link_pago')->count(),
            'meses' => $meses,
            'masVendidos' => PedidoCurso::query()
                ->whereHas('pedido', fn ($q) => $q->whereIn('estado', ['pagado', 'enviado']))
                ->select('curso_id', 'numero', 'titulo', DB::raw('count(*) as veces'), DB::raw('sum(precio) as monto'))
                ->groupBy('curso_id', 'numero', 'titulo')->orderByDesc('veces')->limit(6)->get(),
            'ultimos' => Pedido::with('items')->latest()->limit(8)->get(),
        ]);
    }
}
