<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ajuste;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Datos de la empresa, contacto, cuentas para pagar y textos de la web. */
class ConfiguracionController extends Controller
{
    /** Campos que se guardan como ajustes: clave => [regla, nombre] */
    private const CAMPOS = [
        'nombre' => ['required|string|max:80', 'nombre de la empresa'],
        'eslogan' => ['nullable|string|max:120', 'eslogan'],
        'descripcion' => ['nullable|string|max:1000', 'descripción'],
        'correo_pedidos' => ['required|email|max:120', 'correo de pedidos'],
        'correo_informes' => ['nullable|email|max:120', 'correo de informes'],
        'whatsapp' => ['required|string|max:20', 'WhatsApp'],
        'telefono' => ['nullable|string|max:40', 'teléfono'],
        'horario' => ['nullable|string|max:120', 'horario'],
        'web' => ['nullable|url|max:150', 'página web'],
        'facebook' => ['nullable|url|max:200', 'Facebook'],
        'linkedin' => ['nullable|url|max:200', 'LinkedIn'],
        'youtube' => ['nullable|url|max:200', 'YouTube'],
        'titular' => ['required|string|max:120', 'nombre del comercio'],
        'tipo_cambio' => ['required|numeric|min:1|max:20', 'tipo de cambio'],
        'cuentas' => ['required|string|max:1500', 'cuentas bancarias'],
        'yape_numero' => ['nullable|string|max:20', 'número de Yape'],
        'moneygram' => ['nullable|string|max:300', 'texto para pagos del extranjero'],
        'incluye' => ['nullable|string|max:1000', 'lo que incluye'],
        'dirigido_a' => ['nullable|string|max:3000', 'dirigido a'],
        'envio' => ['nullable|string|max:300', 'mensaje de envío'],
        'por_pagina' => ['required|integer|min:6|max:48', 'cursos por página'],
        'chatbot_activo' => ['required|in:0,1', 'mostrar el asistente'],
        'chatbot_nombre' => ['required|string|max:40', 'nombre del asistente'],
        'chatbot_bienvenida' => ['required|string|max:400', 'mensaje de bienvenida'],
        'chatbot_ia' => ['required|in:0,1', 'respuestas con IA'],
        'chatbot_auto' => ['required|in:panel,saludo,no', 'aparecer al cargar la web'],
        'chatbot_auto_seg' => ['required|integer|min:0|max:30', 'segundos para aparecer'],
        'popup_programas' => ['required|in:0,1', 'ventana de programas'],
    ];

    public function index(Request $request): View
    {
        return view('admin.configuracion.index', ['tab' => $request->query('tab', auth()->user()->puede('configuracion') ? 'empresa' : 'clave')]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $tab = $request->input('tab', 'empresa');
        $campos = array_intersect_key(self::CAMPOS, array_flip(array_keys($request->except(['_token', 'tab']))));
        $datos = $request->validate(
            array_map(fn ($c) => $c[0], $campos),
            ['whatsapp.max' => 'Escriba el WhatsApp con código de país, sin espacios (ej.: 51960995971).'],
            array_map(fn ($c) => $c[1], $campos),
        );
        foreach ($datos as $clave => $valor) {
            Ajuste::guardar($clave, $clave === 'whatsapp' ? preg_replace('/\D/', '', (string) $valor) : $valor);
        }

        return redirect()->route('admin.configuracion', ['tab' => $tab])->with('ok', 'Cambios guardados. Ya se ven en la web.');
    }

    public function clave(Request $request): RedirectResponse
    {
        $request->validate([
            'actual' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], ['password.confirmed' => 'Las contraseñas nuevas no coinciden.'], ['actual' => 'contraseña actual', 'password' => 'contraseña nueva']);

        if (! Hash::check($request->input('actual'), $request->user()->password)) {
            return back()->withErrors(['actual' => 'La contraseña actual no es correcta.']);
        }
        $request->user()->update(['password' => $request->input('password')]);

        return redirect()->route('admin.configuracion', ['tab' => 'clave'])->with('ok', 'Contraseña cambiada.');
    }
}
