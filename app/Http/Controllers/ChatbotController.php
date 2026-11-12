<?php

namespace App\Http\Controllers;

use App\Services\Chatbot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Chat de la web pública (panel lateral) */
class ChatbotController extends Controller
{
    public function bienvenida(Request $request, Chatbot $bot): JsonResponse
    {
        abort_unless((string) config('cursos.chatbot_activo') === '1', 404);

        return response()->json($bot->bienvenida($request->query('curso')));
    }

    public function responder(Request $request, Chatbot $bot): JsonResponse
    {
        abort_unless((string) config('cursos.chatbot_activo') === '1', 404);

        $datos = $request->validate([
            'mensaje' => ['required', 'string', 'max:500'],
            'curso' => ['nullable', 'string', 'max:200'],
            'ultimos' => ['nullable', 'array', 'max:3'],
            'ultimos.*' => ['integer'],
            'historial' => ['nullable', 'array', 'max:8'],
            'historial.*.rol' => ['nullable', 'in:yo,bot'],
            'historial.*.texto' => ['nullable', 'string', 'max:1500'],
        ]);

        return response()->json($bot->responder($datos['mensaje'], $datos));
    }
}
