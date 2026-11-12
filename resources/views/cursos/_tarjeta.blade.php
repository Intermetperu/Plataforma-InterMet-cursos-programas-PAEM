{{-- Tarjeta de un curso en el catálogo --}}
<article class="im-card">
    <div class="im-card-media">
        @include('cursos._media', ['curso' => $curso])
        <div class="im-sellos">
            @if ($curso->es_nuevo)<span class="im-sello nuevo">Nuevo</span>@endif
            @if ($curso->tipo === 'congreso')<span class="im-sello congreso">Congreso</span>@endif
            @if ($curso->idioma === 'en')<span class="im-sello idioma">English</span>@endif
            @if ($curso->en_oferta && $curso->precio_oferta)<span class="im-sello oferta">-{{ round(100 - $curso->precio_oferta * 100 / $curso->precio) }}%</span>@endif
        </div>
        @if ($curso->anio)<span class="im-anio" title="Año en que se realizó">{{ $curso->anio }}</span>@endif
    </div>
    <div class="im-card-cuerpo">
        <div class="im-card-sup">
            @if ($curso->numero)<span class="im-card-num">{{ $curso->numero_texto }}</span>@endif
            <span class="im-card-tema">{{ $curso->tema[0] }}</span>
        </div>
        <h3 class="im-card-titulo"><a href="{{ $curso->url }}">{{ $curso->titulo }}</a></h3>
        <ul class="im-card-meta">
            @if ($curso->instructor)<li title="Instructor"><i class="bx bx-user-voice"></i><span>{{ \Illuminate\Support\Str::limit($curso->instructor, 48) }}</span></li>@endif
            @if ($curso->horas)<li><i class="bx bx-time-five"></i><span>{{ rtrim(rtrim(number_format((float) $curso->horas, 1), '0'), '.') }} horas de video + material</span></li>@endif
            @if ($curso->incluye)<li><i class="bx bx-spreadsheet"></i><span>Incluye: {{ $curso->incluye }}</span></li>@endif
        </ul>
    </div>
    <div class="im-card-pie">
        <div class="im-precio">
            <small>Inversión</small>
            <b>@usd($curso->precio_actual)</b>
            @if ($curso->en_oferta && $curso->precio_oferta)<s>@usd($curso->precio)</s>@endif
        </div>
        @if ($curso->link_pago)
            {{-- Abre el formulario de pago de Niubiz del curso (otra pestaña) y aquí lleva al paso «Registrar mi pago» --}}
            <a href="{{ $curso->link_pago }}" target="_blank" rel="noopener" class="im-btn im-btn-primario" data-comprar="{{ $curso->url }}?pagando=1#comprar"><i class="bx bx-cart-alt"></i> Comprar ahora</a>
        @else
            <a href="{{ $curso->url }}" class="im-btn im-btn-borde"><i class="bx bx-show"></i> Ver curso</a>
        @endif
    </div>
</article>
