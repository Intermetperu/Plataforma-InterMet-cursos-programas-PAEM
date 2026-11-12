{{-- Tarjeta de un programa (portada, página de programas y «otros programas») --}}
<a href="{{ $prog->url }}" class="im-programa im-prog-{{ $prog->color }}">
    <span class="im-programa-cab">
        <span class="im-programa-flecha"><i class="bx bx-right-arrow-alt"></i></span>
        <span class="im-programa-logo">
            <i class="bx {{ $prog->icono }}"></i>
            <span><b>{{ $prog->nombre }}</b><small>{{ $prog->subtitulo }}</small></span>
        </span>
    </span>
    <span class="im-programa-texto">{{ $prog->texto_tarjeta }}</span>
    <span class="im-programa-detalle">{{ $prog->cursos->count() }} {{ $prog->cursos->count() === 1 ? 'curso' : 'cursos' }} · @usd($prog->precio)</span>
    <span class="im-programa-mas">Más detalles -&gt;</span>
</a>
