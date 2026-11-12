<?php

namespace App\Http\Controllers;

use App\Mail\NuevoPedidoAdmin;
use App\Mail\PedidoRecibido;
use App\Models\Curso;
use App\Models\Pedido;
use App\Models\Programa;
use App\Support\Carrito;
use App\Support\Correo;
use App\Support\RevisionCaptura;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * «Registrar pago»: el cliente indica qué cursos pagó, cómo pagó y adjunta la constancia
 * (es la reserva web del hotel, adaptada a la compra de cursos). Luego ve el estado de su pedido.
 */
class PedidoController extends Controller
{
    public function create(Request $request): View
    {
        $todos = Curso::publicados()->orderBy('numero')->orderBy('idioma')->get(['id', 'numero', 'slug', 'titulo', 'idioma', 'anio', 'precio', 'precio_oferta', 'en_oferta']);

        // Cursos ya elegidos: ?curso=slug (o varios), o los que tenía si volvió por un error
        $elegidos = collect(old('cursos', []))->map(fn ($id) => (int) $id);
        if ($elegidos->isEmpty()) {
            $slugs = (array) $request->query('curso', []);
            $elegidos = $todos->whereIn('slug', $slugs)->pluck('id');
        }

        // Programas (PAEM): ?programa=slug (uno o varios, por ejemplo desde el carrito)
        $programas = Programa::publicados()->get(['id', 'numero', 'slug', 'titulo', 'precio']);
        $progElegidos = collect(old('programas', []))->map(fn ($id) => (int) $id);
        if ($progElegidos->isEmpty() && ! old('cursos')) {
            $progElegidos = $programas->whereIn('slug', (array) $request->query('programa', []))->pluck('id');
        }

        return view('pedido.create', [
            'todos' => $todos,
            'elegidos' => $elegidos->values(),
            'programas' => $programas,
            'progElegidos' => $progElegidos->values(),
            'metodos' => Pedido::METODOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Campo trampa para robots
        if (filled($request->input('sitio_web'))) {
            return back()->withInput()->withErrors(['nombre' => 'No pudimos registrar el pedido. Inténtelo de nuevo.']);
        }

        // Solo se paga con el link de pago (Niubiz) y en dólares
        $request->merge(['pago_metodo' => 'niubiz', 'pago_moneda' => 'USD']);
        $esPdfPermitido = true;
        $datos = $request->validate([
            'cursos' => ['nullable', 'array', 'max:40', 'required_without:programas'],
            'cursos.*' => ['integer', Rule::exists('cursos', 'id')->where('publicado', true)],
            'programas' => ['nullable', 'array', 'max:40'],
            'programas.*' => ['integer', Rule::exists('programas', 'id')->where('publicado', true)],
            'nombre' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'celular' => ['required', 'string', 'regex:/^[+\d\s()-]{7,30}$/'],
            'pais' => ['required', 'string', 'max:60'],
            'documento' => ['nullable', 'string', 'max:30'],
            'empresa' => ['nullable', 'string', 'max:150'],
            'pago_metodo' => ['required', Rule::in(array_keys(Pedido::METODOS))],
            'pago_moneda' => ['required', Rule::in(['USD', 'PEN'])],
            'pago_monto' => ['required', 'numeric', 'min:1', 'max:999999'],
            'pago_fecha' => ['required', 'date', 'after:2015-01-01', 'before_or_equal:today'],
            'pago_operacion' => ['required', 'string', 'min:4', 'max:120'],
            'captura' => ['required', 'file', 'max:5120', $esPdfPermitido ? 'mimes:jpg,jpeg,png,webp,pdf' : 'mimes:jpg,jpeg,png,webp'],
            'nota' => ['nullable', 'string', 'max:600'],
        ], [
            'cursos.required_without' => 'Elija al menos un curso o un programa.',
            'celular.regex' => 'Escriba un celular o WhatsApp válido (con código de país si es fuera del Perú).',
            'captura.required' => 'Adjunte la constancia o captura de su pago.',
            'captura.max' => 'La constancia no debe pesar más de 5 MB.',
            'captura.mimes' => $esPdfPermitido ? 'La constancia debe ser una imagen (JPG, PNG) o PDF.' : 'Suba la captura de pantalla de su pago (JPG o PNG).',
            'pago_fecha.before_or_equal' => 'La fecha del pago no puede ser posterior a hoy.',
        ], [
            'nombre' => 'nombre y apellidos', 'email' => 'correo', 'pais' => 'país', 'pago_metodo' => 'medio de pago',
            'pago_moneda' => 'moneda', 'pago_monto' => 'monto pagado', 'pago_fecha' => 'fecha del pago', 'pago_operacion' => 'número de operación',
        ]);

        $email = mb_strtolower(trim($datos['email']));

        // N.º de operación (uno o varios): deben tener números
        $operaciones = Pedido::operaciones($datos['pago_operacion']);
        if (! $operaciones || collect($operaciones)->contains(fn ($o) => preg_match_all('/\d/', $o) < 4)) {
            return back()->withInput()->withErrors(['pago_operacion' => 'Escriba el número de operación tal como sale en su constancia (tiene números).']);
        }
        $operacionNorm = Pedido::operacionesNorm($datos['pago_operacion']);

        // ¿La constancia es un archivo de verdad?
        $revision = RevisionCaptura::revisar($request->file('captura'));
        if (isset($revision['error'])) {
            return back()->withInput()->withErrors(['captura' => $revision['error']]);
        }

        // ¿Ya se registró esta misma constancia o esta operación? (salvo en pedidos rechazados)
        $vigentes = Pedido::where('estado', '!=', 'rechazado');
        $ayuda = ' Si cree que es un error, escríbanos por WhatsApp.';
        if ((clone $vigentes)->where('pago_captura_hash', $revision['hash'])->exists()) {
            return back()->withInput()->withErrors(['captura' => 'Esta constancia ya fue registrada en otro pedido.' . $ayuda]);
        }
        foreach ($operaciones as $op) {
            if ((clone $vigentes)->where('pago_operacion_norm', 'like', "%,{$op},%")->exists()) {
                return back()->withInput()->withErrors(['pago_operacion' => "El número de operación {$op} ya fue registrado en otro pedido." . $ayuda]);
            }
        }
        // Una misma persona no deja muchos pedidos sin revisar
        if (Pedido::where('email', $email)->where('estado', 'por_verificar')->count() >= 3) {
            return back()->withInput()->withErrors(['email' => 'Ya tiene 3 pedidos en revisión. Espere nuestra confirmación o escríbanos por WhatsApp.']);
        }

        $cursos = Curso::whereIn('id', $datos['cursos'] ?? [])->get();
        $programasPedido = Programa::whereIn('id', $datos['programas'] ?? [])->orderBy('orden')->get();
        $total = round($cursos->sum(fn ($c) => $c->precio_actual) + (float) $programasPedido->sum('precio'), 2);

        $alertas = $revision['alertas'];
        if ($revision['huella']) {
            $parecido = (clone $vigentes)->whereNotNull('pago_captura_huella')->where('created_at', '>=', now()->subDays(180))
                ->get(['codigo', 'pago_captura_huella'])
                ->first(fn ($p) => RevisionCaptura::distancia($p->pago_captura_huella, $revision['huella']) <= 3);
            if ($parecido) {
                $alertas[] = "Se ve igual a la constancia del pedido {$parecido->codigo} (puede ser la misma imagen guardada de nuevo).";
            }
        }
        if ((float) $datos['pago_monto'] + 1 < $total) {
            $alertas[] = sprintf('Declara un pago menor al total: USD %s para USD %s.', number_format((float) $datos['pago_monto'], 2), number_format($total, 2));
        }

        $ruta = $request->file('captura')->store('capturas-pago', 'local');

        $pedido = DB::transaction(function () use ($datos, $email, $cursos, $programasPedido, $total, $operacionNorm, $revision, $alertas, $ruta, $request) {
            $pedido = Pedido::create([
                'user_id' => $request->user() && ! $request->user()->esPersonal() ? $request->user()->id : null,
                'nombre' => trim($datos['nombre']),
                'email' => $email,
                'celular' => trim($datos['celular']),
                'pais' => $datos['pais'],
                'documento' => $datos['documento'] ?? null,
                'empresa' => $datos['empresa'] ?? null,
                'nota' => $datos['nota'] ?? null,
                'total' => $total,
                'pago_metodo' => $datos['pago_metodo'],
                'pago_moneda' => $datos['pago_moneda'],
                'pago_monto' => $datos['pago_monto'],
                'pago_fecha' => $datos['pago_fecha'],
                'pago_operacion' => trim($datos['pago_operacion']),
                'pago_operacion_norm' => $operacionNorm,
                'pago_captura' => $ruta,
                'pago_captura_hash' => $revision['hash'],
                'pago_captura_huella' => $revision['huella'],
                'pago_alertas' => ['avisos' => $alertas, 'archivo' => $revision['info']],
                'ip' => $request->ip(),
            ]);
            foreach ($cursos->sortBy('numero') as $c) {
                $pedido->items()->create(['curso_id' => $c->id, 'numero' => $c->numero, 'titulo' => $c->titulo, 'precio' => $c->precio_actual]);
            }
            foreach ($programasPedido as $p) {
                $pedido->items()->create(['programa_id' => $p->id, 'numero' => null, 'titulo' => 'Programa de alta especialización en ' . $p->titulo, 'precio' => $p->precio]);
            }

            return $pedido;
        });

        // Los programas registrados salen del carrito
        foreach ($programasPedido as $p) {
            Carrito::quitar($p);
        }

        // Avisos por correo (si el correo no está configurado, el pedido igual queda registrado)
        Correo::enviar($pedido->email, new PedidoRecibido($pedido));
        Correo::enviar(config('cursos.correo_pedidos'), new NuevoPedidoAdmin($pedido));

        return redirect()->route('pedido.estado', $pedido->token)
            ->with('ok', '¡Recibimos su pago! Lo verificaremos y le enviaremos los enlaces de descarga a ' . $pedido->email . '.');
    }

    public function estado(string $token): View
    {
        $pedido = Pedido::where('token', $token)->with('items.curso')->firstOrFail();

        return view('pedido.estado', compact('pedido'));
    }

    public function consulta(Request $request): View|RedirectResponse
    {
        // Con sesión de cliente: directo a «Mis pedidos»
        if ($request->user() && ! $request->user()->esPersonal()) {
            return redirect()->route('pedido.mios');
        }

        return view('pedido.consulta');
    }

    /** Pedidos del cliente que inició sesión (los de su cuenta y los hechos con su correo) */
    public function mios(Request $request): View|RedirectResponse
    {
        $usuario = $request->user();
        if ($usuario->esPersonal()) {
            return redirect()->route('admin.pedidos.index');
        }

        $pedidos = Pedido::where(fn ($q) => $q->where('user_id', $usuario->id)->orWhere('email', $usuario->email))
            ->with('items')->latest()->get();

        return view('pedido.mios', compact('pedidos', 'usuario'));
    }

    public function buscar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email'],
        ], [], ['codigo' => 'código del pedido', 'email' => 'correo']);

        // «C-12», «c000012» o «12» -> C-000012
        $numero = (int) preg_replace('/\D/', '', $datos['codigo']);
        $pedido = Pedido::where('codigo', 'C-' . str_pad((string) $numero, 6, '0', STR_PAD_LEFT))
            ->where('email', mb_strtolower(trim($datos['email'])))->first();

        if (! $pedido) {
            return back()->withInput()->withErrors(['codigo' => 'No encontramos un pedido con ese código y correo. Revise el correo que le enviamos al registrar su pago.']);
        }

        return redirect()->route('pedido.estado', $pedido->token);
    }
}
