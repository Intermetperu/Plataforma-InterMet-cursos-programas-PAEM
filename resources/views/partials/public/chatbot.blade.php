{{-- Asistente: botón flotante + panel lateral derecho (lo maneja resources/js/modules/chatbot.js) --}}
@php
    $botCurso = request()->route('curso');
    $botSlug = $botCurso instanceof \App\Models\Curso ? $botCurso->slug : null;
    // Aparecer solo al cargar la web: panel (PC) / saludo / no. En formularios no interrumpe.
    $botAuto = request()->routeIs('login', 'registro', 'pedido.create') ? 'no' : (string) config('cursos.chatbot_auto', 'panel');
    $botWa = 'https://wa.me/' . preg_replace('/\D/', '', (string) config('cursos.whatsapp')) . '?text=' . rawurlencode('Hola InterMet, tengo una consulta sobre los cursos digitales.');
@endphp
<div class="im-bot" data-bot
     data-url="{{ route('chatbot.responder') }}"
     data-bienvenida="{{ route('chatbot.bienvenida', array_filter(['curso' => $botSlug])) }}"
     data-curso="{{ $botSlug }}"
     data-wa="{{ $botWa }}"
     data-auto="{{ $botAuto }}"
     data-auto-seg="{{ (int) config('cursos.chatbot_auto_seg', 2) }}">

    {{-- Tarjeta de saludo que aparece sola junto al botón --}}
    <div class="im-bot-avance" data-bot-avance role="status" aria-live="polite" hidden>
        <button type="button" class="im-bot-avance-cerrar" data-bot-avance-cerrar aria-label="Ocultar el saludo"><i class="bx bx-x"></i></button>
        <button type="button" class="im-bot-avance-cab" data-bot-abrir>
            <span class="im-bot-avatar chico"><i class="bx bxs-bot"></i><span class="im-bot-mano" aria-hidden="true">👋</span></span>
            <span><b>{{ config('cursos.chatbot_nombre') }}</b><small><i class="im-bot-enlinea"></i> En línea</small></span>
        </button>
        <div class="im-bot-avance-texto" data-bot-avance-texto></div>
        <div class="im-bot-chips" data-bot-avance-chips></div>
    </div>

    <button type="button" class="im-bot-lanzador" data-bot-abrir aria-controls="im-bot-panel" aria-expanded="false">
        <span class="im-bot-lanzador-icono"><i class="bx bxs-message-rounded-dots"></i><i class="im-bot-enlinea"></i></span>
        <span class="im-bot-lanzador-texto"><b>¿Te ayudo?</b><small>Asistente en línea</small></span>
    </button>

    <div class="im-bot-fondo" data-bot-cerrar></div>

    <section class="im-bot-panel" id="im-bot-panel" role="dialog" aria-labelledby="im-bot-titulo" hidden>
        <header class="im-bot-cab">
            <span class="im-bot-avatar"><i class="bx bxs-bot"></i><span class="im-bot-mano" aria-hidden="true">👋</span></span>
            <div class="im-bot-cab-texto">
                <h2 id="im-bot-titulo">{{ config('cursos.chatbot_nombre') }}</h2>
                <span><i class="im-bot-enlinea"></i> En línea · responde al instante</span>
            </div>
            <button type="button" class="im-bot-icono" data-bot-reiniciar title="Empezar de nuevo" aria-label="Empezar una conversación nueva"><i class="bx bx-refresh"></i></button>
            <button type="button" class="im-bot-icono" data-bot-cerrar title="Cerrar" aria-label="Cerrar el chat"><i class="bx bx-x"></i></button>
        </header>

        <div class="im-bot-mensajes" data-bot-mensajes role="log" aria-live="polite"></div>

        <form class="im-bot-form" data-bot-form autocomplete="off">
            <label class="visually-hidden" for="im-bot-texto">Escribe tu pregunta</label>
            <input id="im-bot-texto" type="text" maxlength="500" placeholder="Escribe tu pregunta…" data-bot-texto>
            <button type="submit" aria-label="Enviar"><i class="bx bxs-send"></i></button>
        </form>
        <p class="im-bot-nota">Asistente automático · <a href="{{ $botWa }}" target="_blank" rel="noopener"><i class="bx bxl-whatsapp"></i> Hablar con una persona</a></p>
    </section>
</div>
