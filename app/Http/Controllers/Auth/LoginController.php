<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Un solo «Iniciar sesión» para todos:
 * - el personal (Administrador, Ventas…) entra al panel;
 * - los clientes vuelven a la web (a la página donde estaban o al inicio).
 */
class LoginController extends Controller
{
    public function create(Request $request): View
    {
        // Si viene desde un curso o desde «Registrar pago», vuelve ahí después de ingresar
        if ($volver = $this->volverA($request)) {
            $request->session()->put('url.intended', $volver);
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [], ['email' => 'correo', 'password' => 'contraseña']);

        if (! Auth::attempt($credenciales, $request->boolean('recordar'))) {
            return back()->withErrors(['email' => 'El correo o la contraseña no son correctos.'])->onlyInput('email');
        }

        $usuario = Auth::user();
        if (! $usuario->activo) {
            Auth::logout();

            return back()->withErrors(['email' => 'Esta cuenta está desactivada. Escríbanos por WhatsApp.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $destino = $request->session()->pull('url.intended');
        $alPanel = $destino && str_starts_with(parse_url($destino, PHP_URL_PATH) ?? '', '/admin');

        // Personal (administrador, ventas…): siempre al panel
        if ($usuario->esPersonal()) {
            return redirect($alPanel ? $destino : route('admin.dashboard'));
        }

        // Cliente: nunca al panel
        if (! $destino || str_contains(parse_url($destino, PHP_URL_PATH) ?? '', '/admin')) {
            $destino = route('home');
        }

        return redirect($destino)->with('aviso', '¡Hola, ' . strtok($usuario->name, ' ') . '! Ya inició sesión.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $eraPersonal = $request->user()?->esPersonal();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $eraPersonal
            ? redirect()->route('login')
            : redirect()->route('home')->with('aviso', 'Cerró sesión. ¡Hasta pronto!');
    }

    /** Solo direcciones de esta misma web */
    private function volverA(Request $request): ?string
    {
        $volver = (string) $request->query('volver');

        return str_starts_with($volver, '/') && ! str_starts_with($volver, '//') ? url($volver) : null;
    }
}
