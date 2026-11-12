<?php

namespace App\Http\Controllers;

use App\Models\Programa;
use App\Support\Carrito;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Carrito: el cliente junta varios programas y paga cada uno con su link de pago */
class CarritoController extends Controller
{
    public function index(): View
    {
        $programas = Carrito::programas();

        return view('carrito.index', [
            'programas' => $programas,
            'total' => round((float) $programas->sum('precio'), 2),
            'valor' => round($programas->sum(fn ($p) => max($p->valor_separado, (float) $p->precio)), 2),
        ]);
    }

    public function agregar(Request $request, Programa $programa): JsonResponse|RedirectResponse
    {
        abort_unless($programa->publicado, 404);
        Carrito::agregar($programa);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'cantidad' => Carrito::cantidad(), 'mensaje' => 'Agregaste «' . $programa->titulo . '» al carrito.']);
        }
        if ($request->boolean('comprar')) {
            return redirect()->route('carrito.index');
        }

        return back()->with('ok', 'Agregaste «' . $programa->titulo . '» al carrito.');
    }

    public function quitar(Request $request, Programa $programa): JsonResponse|RedirectResponse
    {
        Carrito::quitar($programa);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'cantidad' => Carrito::cantidad()]);
        }

        return back()->with('ok', 'Quitamos «' . $programa->titulo . '» del carrito.');
    }

    public function vaciar(): RedirectResponse
    {
        Carrito::vaciar();

        return redirect()->route('carrito.index');
    }
}
