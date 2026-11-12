{{-- Imagen del curso: video de YouTube (abre en ventana), foto subida o portada automática según el tema --}}
@php [$temaNombre, $temaColor, $temaIcono] = $curso->tema; @endphp
@if ($curso->youtube_id)
    <a href="https://www.youtube.com/watch?v={{ $curso->youtube_id }}" class="im-media im-video-pop" aria-label="Ver el video de presentación de {{ $curso->titulo }}">
        <img src="{{ $curso->imagen_url }}" alt="" loading="lazy">
        <span class="im-play" aria-hidden="true"><i class="bx bx-play"></i></span>
        <span class="im-media-etiqueta"><i class="bx bxl-youtube"></i> Ver presentación</span>
    </a>
@elseif ($curso->imagen)
    {{-- La imagen se ve completa; lo que sobra se rellena con la misma imagen difuminada --}}
    <a href="{{ $curso->url }}" class="im-media im-media-foto" tabindex="-1" aria-hidden="true">
        <span class="im-media-fondo" style="background-image: url('{{ $curso->imagen_url }}')"></span>
        <img src="{{ $curso->imagen_url }}" alt="" loading="lazy">
    </a>
@else
    <a href="{{ $curso->url }}" class="im-media im-portada im-t-{{ $temaColor }}" tabindex="-1" aria-hidden="true">
        <span class="im-portada-icono"><i class="bx {{ $temaIcono }}"></i></span>
        <span class="im-portada-tema">{{ $temaNombre }}</span>
        @if ($curso->numero)<span class="im-portada-num">{{ $curso->numero_texto }}</span>@endif
    </a>
@endif
