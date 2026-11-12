@extends('layouts.public')

@section('title', 'Programa de alta especialización en ' . $programa->titulo)
@section('descripcion', \Illuminate\Support\Str::limit('Programa de alta especialización en ' . $programa->titulo . ': ' . $programa->cursos->count() . ' cursos digitales con certificado. ' . ($programa->descripcion ?? ''), 155))

@php
    $wa = preg_replace('/\D/', '', (string) config('cursos.whatsapp'));
    $waProg = rawurlencode("Hola InterMet, tengo una consulta sobre el Programa de alta especialización en {$programa->titulo}.");
    $galeria = $programa->fotos;
    $nCursos = $programa->cursos->count();
    $horas = $programa->horas;
    $valor = $programa->valor_separado;
@endphp

@section('content')
    <section class="im-seccion pt-4">
        <div class="container">
            <nav aria-label="Ruta" class="im-ruta">
                <a href="{{ route('home') }}#programas">Programas de alta especialización</a>
                <i class="bx bx-chevron-right"></i>
                <span>{{ $programa->titulo }}</span>
            </nav>

            @if (request()->boolean('pagando'))
                <div class="im-alerta info mb-3" role="status"><i class="bx bx-info-circle"></i><div>Abrimos la página de pago en otra pestaña. <b>Cuando termine de pagar, registre su pago</b> para recibir los cursos y su certificado.</div></div>
            @endif

            <div class="im-producto">
                <div class="row g-4">
                    {{-- ================= GALERÍA ================= --}}
                    <div class="col-lg-5">
                        <div class="im-galeria" data-galeria>
                            @if (count($galeria) > 1)
                                <div class="im-galeria-miniaturas">
                                    @foreach ($galeria as $i => $foto)
                                        <button type="button" class="im-galeria-mini {{ $i === 0 ? 'activa' : '' }}" data-foto="{{ $foto['src'] }}" aria-label="Ver imagen {{ $i + 1 }}"><img src="{{ $foto['src'] }}" alt="" loading="lazy"></button>
                                    @endforeach
                                </div>
                            @endif
                            <div class="im-galeria-grande">
                                @if ($galeria)
                                    <span class="im-media-fondo" data-foto-fondo style="background-image: url('{{ $galeria[0]['src'] }}')"></span>
                                    <img src="{{ $galeria[0]['src'] }}" alt="{{ $galeria[0]['alt'] }}" data-foto-grande>
                                @else
                                    <div class="im-programa im-prog-{{ $programa->color }}">
                                        <span class="im-programa-cab">
                                            <span class="im-programa-flecha"><i class="bx bx-award"></i></span>
                                            <span class="im-programa-logo"><i class="bx {{ $programa->icono }}"></i><span><b>{{ $programa->nombre }}</b><small>{{ $programa->subtitulo }}</small></span></span>
                                        </span>
                                        <span class="im-programa-texto">{{ $programa->texto_tarjeta }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- ================= DATOS ================= --}}
                    <div class="col-lg-4 col-md-7">
                        <span class="im-producto-sello"><i class="bx {{ $programa->icono }}"></i> Programa de alta especialización</span>
                        <h1>{{ $programa->titulo }}</h1>
                        <div class="im-producto-meta">
                            <span><i class="bx bx-book-content"></i> {{ $nCursos }} {{ $nCursos === 1 ? 'curso incluido' : 'cursos incluidos' }}</span>
                            @if ($horas)<span><i class="bx bx-time-five"></i> {{ rtrim(rtrim(number_format($horas, 1), '0'), '.') }} horas de video</span>@endif
                            <span><i class="bx bx-award"></i> Con certificado</span>
                        </div>

                        @if ($programa->ahorro > 0)
                            <div class="im-precio-antes">@usd($valor)</div>
                        @endif
                        <div class="im-precio-ahora">
                            <b>@usd($programa->precio)</b>
                            @if ($programa->ahorro > 0)<span class="im-precio-off">{{ $programa->ahorro }}% OFF</span>@endif
                        </div>
                        @if ($programa->ahorro > 0)
                            <div class="im-precio-nota">Ahorras @usd($valor - (float) $programa->precio) frente a comprar los cursos por separado</div>
                        @endif

                        <h2 class="h6 fw-bold mt-3 mb-0">Lo que tienes que saber de este programa</h2>
                        <ul class="im-lista-clave">
                            <li>Incluye {{ $nCursos === 1 ? '1 curso digital' : $nCursos . ' cursos digitales' }} completos (videos + material de trabajo).</li>
                            <li><b>Certificado digital</b> InterMet del programa, con la fecha que elijas.</li>
                            <li>Recibes todo por enlaces de descarga en tu correo: los cursos son tuyos y no vencen.</li>
                            <li>Expertos internacionales y casos reales de plantas mineras.</li>
                            @if ($programa->descripcion)<li>{{ $programa->descripcion }}</li>@endif
                        </ul>
                    </div>

                    {{-- ================= COMPRA ================= --}}
                    <div class="col-lg-3 col-md-5">
                        <div class="im-caja-compra" id="comprar">
                            @if ($programa->ahorro > 0)<div class="im-precio-antes">@usd($valor)</div>@endif
                            <div class="im-precio-ahora mb-3"><b style="font-size:30px">@usd($programa->precio)</b>@if ($programa->ahorro > 0)<span class="im-precio-off">{{ $programa->ahorro }}% OFF</span>@endif</div>

                            @if ($programa->link_pago)
                                <a href="{{ $programa->link_pago }}" target="_blank" rel="noopener" class="im-btn im-btn-primario" data-comprar="{{ $programa->url }}?pagando=1#comprar"><i class="bx bx-credit-card"></i> Comprar ahora</a>
                            @else
                                <form method="POST" action="{{ route('carrito.agregar', $programa) }}">@csrf<input type="hidden" name="comprar" value="1"><button type="submit" class="im-btn im-btn-primario w-100"><i class="bx bx-credit-card"></i> Comprar ahora</button></form>
                            @endif
                            <form method="POST" action="{{ route('carrito.agregar', $programa) }}" data-agregar-carrito>
                                @csrf
                                <button type="submit" class="im-btn im-btn-carrito w-100 {{ $enCarrito ? 'agregado' : '' }}" data-texto-agregado="En tu carrito">
                                    @if ($enCarrito)<i class="bx bx-check"></i> <span>En tu carrito</span>@else<i class="bx bx-cart-add"></i> <span>Agregar al carrito</span>@endif
                                </button>
                            </form>

                            @if (request()->boolean('pagando'))
                                <a href="{{ route('pedido.create', ['programa' => $programa->slug]) }}" class="im-btn im-btn-oscuro"><i class="bx bx-receipt"></i> Registrar mi pago</a>
                            @endif

                            <div class="im-caja-linea"><i class="bx bx-lock-alt"></i><span>Pago seguro con tarjeta por link de pago (Niubiz)</span></div>
                            <div class="im-caja-linea"><i class="bx bx-download"></i><span>{{ config('cursos.envio') }}</span></div>
                            <div class="im-caja-linea"><i class="bx bx-award"></i><span>Certificado digital del programa incluido</span></div>
                            <a class="im-btn im-btn-wa mt-3" href="https://wa.me/{{ $wa }}?text={{ $waProg }}" target="_blank" rel="noopener"><i class="bx bxl-whatsapp"></i> Preguntar por WhatsApp</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= TEMARIO ================= --}}
            @php $modulos = $programa->modulos; @endphp
            @if ($modulos)
                <div class="im-titulo-seccion mt-5">
                    <div><span class="im-antetitulo">Programa de estudios</span><h2>Temario</h2></div>
                </div>
                <div class="im-prog-temario" data-temario>
                    @foreach ($modulos as $i => $m)
                        <details class="im-prog-modulo" @if ($i === 0) open @endif>
                            <summary>
                                <span class="im-prog-modulo-num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="im-prog-modulo-titulo"><b>{{ $m['titulo'] }}</b>@if (! empty($m['detalle']))<small>{{ $m['detalle'] }}</small>@elseif ($m['temas'])<small>{{ count($m['temas']) }} {{ count($m['temas']) === 1 ? 'tema' : 'temas' }}</small>@endif</span>
                                @if ($m['temas'])<i class="bx bx-chevron-down"></i>@endif
                            </summary>
                            @if ($m['temas'])
                                <ul>@foreach ($m['temas'] as $t)<li><i class="bx bx-check-circle"></i> {{ $t }}</li>@endforeach</ul>
                            @endif
                        </details>
                    @endforeach
                </div>
            @endif

            {{-- ================= CURSOS INCLUIDOS ================= --}}
            <div class="im-titulo-seccion mt-5">
                <div><span class="im-antetitulo">Este programa incluye</span><h2>{{ $nCursos === 1 ? '1 curso digital' : $nCursos . ' cursos digitales' }}</h2></div>
            </div>
            <div class="im-cursos-incluidos">
                @foreach ($programa->cursos as $c)
                    @php [$tn, $tc, $ti] = $c->tema; $h = $c->horas ? rtrim(rtrim(number_format((float) $c->horas, 1), '0'), '.') : null; @endphp
                    <a @if ($c->publicado) href="{{ $c->url }}" @endif class="im-curso-fila">
                        @if ($c->imagen)
                            <span class="im-media im-media-foto"><span class="im-media-fondo" style="background-image: url('{{ $c->imagen_url }}')"></span><img src="{{ $c->imagen_url }}" alt="" loading="lazy"></span>
                        @elseif ($c->youtube_id)
                            <span class="im-media"><img src="https://i.ytimg.com/vi/{{ $c->youtube_id }}/hqdefault.jpg" alt="" loading="lazy"></span>
                        @else
                            <span class="im-media im-portada im-t-{{ $tc }}"><span class="im-portada-icono"><i class="bx {{ $ti }}"></i></span></span>
                        @endif
                        <span class="im-curso-fila-txt">
                            <small>@if ($c->numero)<b>{{ $c->numero_texto }}</b> · @endif{{ $tn }}@if ($c->anio) · {{ $c->anio }}@endif @if ($c->tipo === 'congreso') · Congreso @endif</small>
                            <h3>{{ $c->titulo }}</h3>
                            <p>
                                @if ($c->instructor){{ \Illuminate\Support\Str::limit($c->instructor, 70) }}@endif
                                @if ($h) · {{ $h }} h de video @endif
                                @if (! $c->publicado && (int) $c->anio >= now()->year) <span class="im-pronto-chip">Se entrega al realizarse</span> @endif
                            </p>
                        </span>
                        @if ((float) $c->precio > 0)
                            <span class="im-curso-fila-precio">Por separado<s>@usd($c->precio)</s></span>
                        @endif
                    </a>
                @endforeach
            </div>

            {{-- ================= OTROS PROGRAMAS ================= --}}
            @if ($otros->isNotEmpty())
                <div class="im-titulo-seccion mt-5">
                    <div><span class="im-antetitulo">Siga especializándose</span><h2>Otros programas</h2></div>
                </div>
                <div class="im-programas-carrusel" data-carrusel>
                    @foreach ($otros as $prog)
                        <div class="im-programas-item">
                            <a href="{{ $prog->url }}" class="im-programa im-prog-{{ $prog->color }}">
                                <span class="im-programa-cab">
                                    <span class="im-programa-flecha"><i class="bx bx-right-arrow-alt"></i></span>
                                    <span class="im-programa-logo"><i class="bx {{ $prog->icono }}"></i><span><b>{{ $prog->nombre }}</b><small>{{ $prog->subtitulo }}</small></span></span>
                                </span>
                                <span class="im-programa-texto">{{ $prog->texto_tarjeta }}</span>
                                <span class="im-programa-detalle">{{ $prog->cursos->count() }} {{ $prog->cursos->count() === 1 ? 'curso' : 'cursos' }} · @usd($prog->precio)</span>
                                <span class="im-programa-mas">Más detalles -&gt;</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Barra fija en el celular --}}
    <div class="im-barra-compra">
        <div><small>Programa</small><b>@usd($programa->precio)</b></div>
        <a href="#comprar" class="im-btn im-btn-primario"><i class="bx bx-cart-alt"></i> Comprar</a>
    </div>
@endsection
