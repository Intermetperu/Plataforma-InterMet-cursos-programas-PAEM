<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\PedidoCurso;
use App\Support\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/** Ventas verificadas por mes, por curso y por medio de pago (con exportación a Excel). */
class ReporteController extends Controller
{
    public function index(Request $request): Response|\Illuminate\View\View
    {
        $desde = Carbon::parse($request->query('desde', now()->startOfYear()->toDateString()))->startOfDay();
        $hasta = Carbon::parse($request->query('hasta', now()->toDateString()))->endOfDay();
        $pedidos = Pedido::whereIn('estado', ['pagado', 'enviado'])->whereBetween('verificado_en', [$desde, $hasta]);

        $porMes = (clone $pedidos)->get(['verificado_en', 'total'])->groupBy(fn ($p) => $p->verificado_en->format('Y-m'))
            ->map(fn ($g) => ['pedidos' => $g->count(), 'total' => $g->sum('total')])->sortKeys();
        $porMetodo = (clone $pedidos)->select('pago_metodo', DB::raw('count(*) as pedidos'), DB::raw('sum(total) as total'))->groupBy('pago_metodo')->orderByDesc('total')->get();
        $porCurso = PedidoCurso::whereIn('pedido_id', (clone $pedidos)->select('id'))
            ->select('numero', 'titulo', DB::raw('count(*) as veces'), DB::raw('sum(precio) as total'))
            ->groupBy('numero', 'titulo')->orderByDesc('total')->get();

        if ($request->query('exportar')) {
            $filas = [['Pedido', 'Fecha verificado', 'Cliente', 'Correo', 'País', 'Cursos', 'Total USD', 'Medio', 'Moneda', 'Monto pagado', 'Operación', 'Estado']];
            foreach ((clone $pedidos)->with('items')->orderBy('verificado_en')->get() as $p) {
                $filas[] = [$p->codigo, $p->verificado_en->format('d/m/Y'), $p->nombre, $p->email, $p->pais,
                    $p->items->map(fn ($i) => ($i->numero ? '#' . $i->numero . ' ' : '') . $i->titulo)->implode(' | '),
                    (float) $p->total, $p->metodo_texto, $p->pago_moneda, (float) $p->pago_monto, $p->pago_operacion, $p->estado_texto];
            }

            return response(Excel::escribir($filas, [10, 14, 28, 28, 12, 70, 10, 26, 8, 12, 14, 14]), 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="ventas-' . $desde->format('Ymd') . '-' . $hasta->format('Ymd') . '.xlsx"',
            ]);
        }

        return view('admin.reportes', [
            'desde' => $desde, 'hasta' => $hasta,
            'total' => (float) (clone $pedidos)->sum('total'),
            'cantidad' => (clone $pedidos)->count(),
            'porMes' => $porMes, 'porMetodo' => $porMetodo, 'porCurso' => $porCurso,
        ]);
    }
}
