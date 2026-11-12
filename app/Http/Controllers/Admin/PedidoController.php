<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccesoEnviado;
use App\Mail\PedidoRechazado;
use App\Models\Pedido;
use App\Support\Correo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pedidos de la web: revisar la constancia, marcar el pago como verificado,
 * enviar los enlaces de descarga (correo + WhatsApp) o rechazar con un motivo.
 */
class PedidoController extends Controller
{
    public function index(Request $request): View
    {
        $estado = array_key_exists($request->query('estado'), Pedido::ESTADOS) ? $request->query('estado') : null;
        $q = trim((string) $request->query('q'));
        // Por defecto se abre en «Por verificar» si hay alguno
        if (! $estado && $q === '' && ! $request->has('estado') && Pedido::where('estado', 'por_verificar')->exists()) {
            $estado = 'por_verificar';
        }

        $pedidos = Pedido::with('items')
            ->when($estado, fn ($p) => $p->where('estado', $estado))
            ->when($q !== '', fn ($p) => $p->where(fn ($w) => $w->where('codigo', 'like', "%$q%")->orWhere('nombre', 'like', "%$q%")
                ->orWhere('email', 'like', "%$q%")->orWhere('celular', 'like', "%$q%")->orWhere('pago_operacion', 'like', "%$q%")))
            ->when($estado === 'por_verificar', fn ($p) => $p->oldest(), fn ($p) => $p->latest())
            ->paginate(20)->withQueryString();

        return view('admin.pedidos.index', [
            'pedidos' => $pedidos,
            'estado' => $estado,
            'q' => $q,
            'cuentas' => Pedido::selectRaw('estado, count(*) as n')->groupBy('estado')->pluck('n', 'estado'),
        ]);
    }

    public function show(Pedido $pedido): View
    {
        $pedido->load(['items.curso', 'items.programa.cursos', 'atendidoPor']);

        return view('admin.pedidos.show', [
            'pedido' => $pedido,
            'otros' => Pedido::where('email', $pedido->email)->whereKeyNot($pedido->id)->latest()->limit(5)->get(),
        ]);
    }

    /** La constancia está guardada en privado: solo el personal la ve */
    public function captura(Pedido $pedido): StreamedResponse
    {
        abort_unless($pedido->pago_captura && Storage::disk('local')->exists($pedido->pago_captura), 404);

        return Storage::disk('local')->response($pedido->pago_captura);
    }

    /** El pago llegó a la cuenta (los enlaces se envían después) */
    public function verificar(Request $request, Pedido $pedido): RedirectResponse
    {
        abort_unless($pedido->estado === 'por_verificar', 422, 'Este pedido ya fue atendido.');
        $pedido->update(['estado' => 'pagado', 'verificado_en' => now(), 'atendido_por' => $request->user()->id]);

        return back()->with('ok', "Pago del pedido {$pedido->codigo} verificado. Ahora envíe los enlaces de descarga.");
    }

    /** Envía (o reenvía) los enlaces de descarga al cliente */
    public function enviar(Request $request, Pedido $pedido): RedirectResponse
    {
        abort_if($pedido->estado === 'rechazado', 422, 'El pedido está rechazado. Reábralo primero.');
        $datos = $request->validate([
            'enlace_descarga' => ['required', 'string', 'max:3000'],
            'mensaje_cliente' => ['nullable', 'string', 'max:2000'],
        ], [], ['enlace_descarga' => 'enlaces de descarga', 'mensaje_cliente' => 'mensaje']);

        $enlaces = collect(preg_split('/\s+/', $datos['enlace_descarga']))->filter()->values();
        $malos = $enlaces->reject(fn ($e) => filter_var($e, FILTER_VALIDATE_URL) && preg_match('~^https?://~i', $e));
        if ($enlaces->isEmpty() || $malos->isNotEmpty()) {
            return back()->withInput()->withErrors(['enlace_descarga' => 'Escriba enlaces completos que empiecen con https:// (uno por línea).']);
        }

        $primeraVez = $pedido->estado !== 'enviado';
        DB::transaction(function () use ($pedido, $enlaces, $datos, $request, $primeraVez) {
            $pedido->update([
                'estado' => 'enviado',
                'enlace_descarga' => $enlaces->implode("\n"),
                'mensaje_cliente' => $datos['mensaje_cliente'] ?? null,
                'verificado_en' => $pedido->verificado_en ?? now(),
                'enviado_en' => now(),
                'atendido_por' => $request->user()->id,
            ]);
            if ($primeraVez) {
                foreach ($pedido->items as $item) {
                    $item->curso?->increment('vendidos');
                }
            }
        });

        $ok = Correo::enviar($pedido->email, new AccesoEnviado($pedido->fresh('items')));
        $texto = $ok
            ? "Enlaces enviados a {$pedido->email}."
            : 'Pedido marcado como enviado, pero el correo no salió (revise la configuración de correo). Envíele los enlaces por WhatsApp con el botón verde.';

        return back()->with($ok ? 'ok' : 'error', $texto);
    }

    public function rechazar(Request $request, Pedido $pedido): RedirectResponse
    {
        $datos = $request->validate(['motivo_rechazo' => ['required', 'string', 'min:5', 'max:500']], [], ['motivo_rechazo' => 'motivo']);
        abort_if($pedido->estado === 'enviado', 422, 'Este pedido ya tiene el acceso enviado.');

        $pedido->update(['estado' => 'rechazado', 'motivo_rechazo' => $datos['motivo_rechazo'], 'atendido_por' => $request->user()->id]);
        $ok = Correo::enviar($pedido->email, new PedidoRechazado($pedido));

        return back()->with('ok', "Pedido {$pedido->codigo} rechazado." . ($ok ? ' Se avisó al cliente por correo.' : ' Avísele por WhatsApp (el correo no está configurado).'));
    }

    /** Volver a «Por verificar» (si se rechazó por error) */
    public function reabrir(Pedido $pedido): RedirectResponse
    {
        abort_unless($pedido->estado === 'rechazado', 422);
        $pedido->update(['estado' => 'por_verificar', 'motivo_rechazo' => null]);

        return back()->with('ok', "Pedido {$pedido->codigo} vuelve a «Por verificar».");
    }
}
