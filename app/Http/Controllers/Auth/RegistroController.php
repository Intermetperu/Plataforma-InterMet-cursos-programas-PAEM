<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** «Registrarse»: cualquier persona crea su cuenta de cliente y vuelve a la web. */
class RegistroController extends Controller
{
    public function create(Request $request): View
    {
        $volver = (string) $request->query('volver');
        if (str_starts_with($volver, '/') && ! str_starts_with($volver, '//')) {
            $request->session()->put('url.intended', url($volver));
        }

        return view('auth.registro');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $datos = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'celular' => ['required', 'string', 'regex:/^[+\d\s()-]{7,30}$/'],
            'pais' => ['required', 'string', 'max:60'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.unique' => 'Ya existe una cuenta con este correo. Inicie sesión.',
            'celular.regex' => 'Escriba un celular o WhatsApp válido.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ], ['name' => 'nombre y apellidos', 'email' => 'correo', 'pais' => 'país', 'password' => 'contraseña']);

        // Sin rol = cliente: no puede entrar al panel
        $usuario = User::create($datos + ['role_id' => null, 'activo' => true]);

        // Sus pedidos anteriores con el mismo correo pasan a su cuenta
        \App\Models\Pedido::whereNull('user_id')->where('email', $usuario->email)->update(['user_id' => $usuario->id]);

        Auth::login($usuario);
        $request->session()->regenerate();

        $destino = $request->session()->pull('url.intended');
        if (! $destino || str_contains(parse_url($destino, PHP_URL_PATH) ?? '', '/admin')) {
            $destino = route('home');
        }

        return redirect($destino)->with('aviso', '¡Bienvenido, ' . strtok($usuario->name, ' ') . '! Su cuenta está lista. Elija su curso y toque «Comprar ahora».');
    }
}
