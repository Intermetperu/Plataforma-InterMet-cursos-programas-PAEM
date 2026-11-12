@extends('layouts.public')

@section('title', 'Cursos y congresos digitales')
@section('descripcion', 'Cursos y congresos digitales de minería con expertos de los 5 continentes. Grabación MP4, material de trabajo y certificado.')

@php
    // Enlace que conserva los filtros actuales y cambia solo uno
    $con = fn (array $cambios) => route('home', array_filter(array_merge($filtros, ['page' => null], $cambios), fn ($v) => $v !== null && $v !== '' && ! ($v === 'recientes'))) . '#catalogo';
    $hayFiltros = $filtros['q'] !== '' || $filtros['anio'] || $filtros['idioma'];
@endphp

@section('content')
    {{-- ================= PORTADA ================= --}}
    <section class="im-hero">
        <div class="container position-relative">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="im-hero-etiqueta"><i class="bx bx-play-circle"></i> Grabados · véalos cuando quiera</span>
                    <h1>Cursos y congresos <span>digitales</span> de minería</h1>
                    <p class="im-hero-texto">Realizados por InterMet con expertos de primer nivel de los 5 continentes. Compre en línea y reciba los enlaces de descarga en su correo.</p>
                    <form class="im-hero-buscar" method="GET" action="{{ route('home') }}#catalogo" role="search">
                        <i class="bx bx-search" aria-hidden="true"></i>
                        <input type="search" name="q" value="{{ $filtros['q'] }}" placeholder="Busque por tema, instructor o N° (ej.: flotación, Pitard, METSIM)" aria-label="Buscar cursos">
                        <button type="submit" class="im-btn im-btn-acento">Buscar</button>
                    </form>
                    <div class="im-hero-sugerencias">
                        <span>Populares:</span>
                        @foreach (['Flotación', 'Molienda', 'Muestreo', 'METSIM', 'Machine Learning', 'Relaves'] as $s)
                            <a href="{{ route('home', ['q' => $s]) }}#catalogo">{{ $s }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="im-hero-cifras">
                        <div><b>{{ $totalCatalogo }}</b><span>cursos y congresos</span></div>
                        <div><b>{{ $rangoAnios[0] }}–{{ $rangoAnios[1] }}</b><span>años de contenido</span></div>
                        <div><b>5</b><span>continentes de expertos</span></div>
                        <div><b>MP4</b><span>+ material + certificado</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= PROGRAMAS DE ALTA ESPECIALIZACIÓN ================= --}}
    <section class="im-programas" id="programas">
        <div class="container">
            <div class="im-programas-cab">
                <div>
                    <h2 class="im-programas-titulo">Programas de alta especialización</h2>
                    @if ($programas->isNotEmpty())<p class="im-programas-sub">{{ $programas->count() }} programas · cada uno reúne varios cursos digitales con certificado</p>@endif
                </div>
                @if ($programas->count() > 4)
                    <div class="im-programas-flechas align-items-center">
                        <a href="{{ route('programas.index') }}" class="im-btn im-btn-primario im-programas-ver-btn"><i class="bx bx-grid-alt"></i> Ver los {{ $programas->count() }} programas</a>
                        <button type="button" class="im-programas-flecha" data-carrusel-ant aria-label="Programas anteriores"><i class="bx bx-chevron-left"></i></button>
                        <button type="button" class="im-programas-flecha" data-carrusel-sig aria-label="Más programas"><i class="bx bx-chevron-right"></i></button>
                    </div>
                @endif
            </div>
            @if ($programas->isNotEmpty())
                <div class="im-programas-carrusel" data-carrusel>
                    @foreach ($programas as $prog)
                        <div class="im-programas-item">
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
                        </div>
                    @endforeach
                </div>
                <a href="{{ route('programas.index') }}" class="im-programas-franja">
                    <span class="im-programas-franja-icono"><i class="bx bx-award"></i></span>
                    <span class="im-programas-franja-txt">
                        <b>{{ $programas->count() }} programas de alta especialización minera</b>
                        <small>Busca por tema · varios cursos con certificado · hasta {{ $programas->max('ahorro') }}% de ahorro frente a comprarlos por separado</small>
                    </span>
                    <span class="im-programas-franja-btn">Ver todos los programas <i class="bx bx-right-arrow-alt"></i></span>
                </a>
            @else
            <div class="row g-4">
                @foreach (config('cursos.programas', []) as $prog)
                    @php
                        $enlace = $prog['enlace'] ?? null;
                        $waProg = 'https://wa.me/' . preg_replace('/\D/', '', (string) config('cursos.whatsapp')) . '?text=' . rawurlencode('Hola InterMet, quiero que me avisen cuando lancen el ' . $prog['titulo'] . '.');
                    @endphp
                    <div class="col-sm-6 col-xl-3">
                        <a href="{{ $enlace ?: '#programa-pronto' }}" class="im-programa im-prog-{{ $prog['color'] ?? 'azul' }}"
                           @if ($enlace && \Illuminate\Support\Str::startsWith($enlace, 'http')) target="_blank" rel="noopener" @endif
                           @unless ($enlace) data-pronto data-nombre="{{ $prog['nombre'] }} {{ $prog['apellido'] ?? '' }}" data-icono="{{ $prog['icono'] ?? 'bx-award' }}" data-color="{{ $prog['color'] ?? 'azul' }}" data-wa="{{ $waProg }}" @endunless>
                            <span class="im-programa-cab">
                                <span class="im-programa-flecha"><i class="bx bx-right-arrow-alt"></i></span>
                                <span class="im-programa-logo">
                                    <i class="bx {{ $prog['icono'] ?? 'bx-award' }}"></i>
                                    <span><b>{{ $prog['nombre'] }}</b><small>{{ $prog['apellido'] ?? '' }}</small></span>
                                </span>
                            </span>
                            <span class="im-programa-texto">{{ $prog['titulo'] }}</span>
                            <span class="im-programa-mas">Más detalles -&gt;</span>
                        </a>
                    </div>
                @endforeach
            </div>
            @endif
        </div>
    </section>

    {{-- Ventana de bienvenida: presenta los programas (una vez al día) --}}
    @if ($programas->isNotEmpty() && (string) config('cursos.popup_programas', '1') === '1')
        <div class="im-promo" data-promo role="dialog" aria-modal="true" aria-labelledby="promo-titulo" hidden>
            <div class="im-promo-fondo" data-promo-cerrar></div>
            <div class="im-promo-caja">
                <button type="button" class="im-promo-cerrar" data-promo-cerrar aria-label="Cerrar"><i class="bx bx-x"></i></button>
                <div class="im-promo-izq">
                    <span class="im-promo-nuevo"><i class="bx bxs-star"></i> Nuevo</span>
                    <h2 id="promo-titulo">Programas de Alta Especialización Minera</h2>
                    <p>Los mejores congresos de cada tema reunidos en un solo programa, con <b>certificado digital</b> y a un precio especial.</p>
                    <div class="im-promo-cifras">
                        <div><b>{{ $programas->count() }}</b><span>programas</span></div>
                        <div><b>{{ $programas->max('ahorro') }}%</b><span>de ahorro</span></div>
                        <div><b><i class="bx bx-award"></i></b><span>certificado</span></div>
                    </div>
                </div>
                <div class="im-promo-der">
                    <small class="im-promo-sub">Los más elegidos</small>
                    @foreach ($destacados as $d)
                        <a href="{{ $d->url }}" class="im-promo-prog">
                            <span class="im-promo-prog-icono im-prog-{{ $d->color }}"><i class="bx {{ $d->icono }}"></i></span>
                            <span class="im-promo-prog-txt"><b>{{ $d->titulo }}</b><small>@usd($d->precio) · con certificado</small></span>
                            @if ($d->ahorro)<span class="im-promo-prog-off">{{ $d->ahorro }}% OFF</span>@endif
                        </a>
                    @endforeach
                    <a href="{{ route('programas.index') }}" class="im-btn im-promo-btn"><i class="bx bx-grid-alt"></i> Ver los {{ $programas->count() }} programas</a>
                    <button type="button" class="im-promo-luego" data-promo-cerrar>Ahora no, gracias</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Ventana «Próximamente» de los programas --}}
    <div class="im-pronto" id="programa-pronto" role="dialog" aria-modal="true" aria-labelledby="pronto-titulo" hidden>
        <div class="im-pronto-fondo" data-cerrar-pronto></div>
        <div class="im-pronto-caja">
            <button type="button" class="im-pronto-cerrar" data-cerrar-pronto aria-label="Cerrar"><i class="bx bx-x"></i></button>
            <div class="im-pronto-cab" data-pronto-cab>
                <span class="im-pronto-icono"><i class="bx" data-pronto-icono></i></span>
                <span class="im-pronto-sello"><i class="bx bx-time-five"></i> Próximamente</span>
            </div>
            <div class="im-pronto-cuerpo">
                <small>Programa de alta especialización</small>
                <h3 id="pronto-titulo" data-pronto-nombre></h3>
                <p>Nuestro equipo está preparando este programa con expertos internacionales. <b>Muy pronto anunciaremos el lanzamiento</b>, las fechas y el temario.</p>
                <p class="mb-4">¿Quiere ser de los primeros en enterarse? Escríbanos y le avisamos apenas abramos las inscripciones.</p>
                <a href="#" class="im-btn im-btn-wa w-100" data-pronto-wa target="_blank" rel="noopener"><i class="bx bxl-whatsapp"></i> Quiero que me avisen</a>
                <button type="button" class="im-pronto-luego" data-cerrar-pronto>Seguir viendo los cursos</button>
            </div>
        </div>
    </div>

    {{-- ================= CATÁLOGO ================= --}}
    <section class="im-seccion" id="catalogo">
        <div class="container">
            <div class="im-titulo-seccion">
                <div><span class="im-antetitulo">Catálogo completo</span><h2>Cursos y congresos digitales</h2></div>
            </div>

            {{-- Filtros: año en que salió, idioma y orden --}}
            <form class="im-filtros" method="GET" action="{{ route('home') }}#catalogo" data-filtros>
                <div class="im-filtros-fila">
                    <label class="im-campo-buscar">
                        <i class="bx bx-search" aria-hidden="true"></i>
                        <input type="search" name="q" value="{{ $filtros['q'] }}" placeholder="Buscar en el catálogo" aria-label="Buscar en el catálogo">
                    </label>
                    @if ($filtros['anio'])<input type="hidden" name="anio" value="{{ $filtros['anio'] }}">@endif
                    <label class="im-select">
                        <span>Idioma</span>
                        <select name="idioma" data-auto>
                            <option value="">Todos</option>
                            @foreach (\App\Models\Curso::IDIOMAS as $k => $v)
                                <option value="{{ $k }}" @selected($filtros['idioma'] === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="im-select">
                        <span>Ordenar</span>
                        <select name="orden" data-auto>
                            @foreach ($ordenes as $k => $v)
                                <option value="{{ $k }}" @selected($filtros['orden'] === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="im-btn im-btn-primario d-md-none">Aplicar</button>
                </div>

                <div class="im-anios" role="group" aria-label="Año en que salió el curso">
                    <span class="im-anios-titulo"><i class="bx bx-calendar"></i> Año de publicación</span>
                    <div class="im-anios-lista">
                        <a href="{{ $con(['anio' => null]) }}" class="im-chip {{ ! $filtros['anio'] ? 'activo' : '' }}">Todos <small>{{ $porAnio->sum() }}</small></a>
                        @foreach ($porAnio as $a => $n)
                            <a href="{{ $con(['anio' => $a ?: 'sin']) }}" class="im-chip {{ (string) $filtros['anio'] === (string) ($a ?: 'sin') ? 'activo' : '' }}">{{ $a ?: 'Sin año' }} <small>{{ $n }}</small></a>
                        @endforeach
                    </div>
                </div>
            </form>

            <div class="im-resultado">
                <p>
                    @if ($cursos->total())
                        Mostrando <b>{{ $cursos->firstItem() }}–{{ $cursos->lastItem() }}</b> de <b>{{ $cursos->total() }}</b>
                        {{ $cursos->total() === 1 ? 'curso' : 'cursos' }}
                        @if ($filtros['anio']) de <b>{{ $filtros['anio'] === 'sin' ? 'año sin indicar' : $filtros['anio'] }}</b>@endif
                        @if ($filtros['q'] !== '') para «<b>{{ $filtros['q'] }}</b>»@endif
                    @else
                        No encontramos cursos @if ($filtros['q'] !== '') para «<b>{{ $filtros['q'] }}</b>»@endif con estos filtros.
                    @endif
                </p>
                @if ($hayFiltros)
                    <a href="{{ route('home') }}#catalogo" class="im-link-limpiar"><i class="bx bx-x"></i> Quitar filtros</a>
                @endif
            </div>

            @if ($cursos->isEmpty())
                <div class="im-vacio">
                    <i class="bx bx-search-alt"></i>
                    <h3>Sin resultados</h3>
                    <p>Pruebe con otra palabra o escríbanos: le ayudamos a encontrar el curso que necesita.</p>
                    <a href="{{ route('home') }}#catalogo" class="im-btn im-btn-primario">Ver todo el catálogo</a>
                </div>
            @else
                <div class="row g-4">
                    @foreach ($cursos as $curso)
                        <div class="col-sm-6 col-lg-4">@include('cursos._tarjeta', ['curso' => $curso])</div>
                    @endforeach
                </div>
                <div class="im-paginacion">{{ $cursos->onEachSide(1)->links() }}</div>
            @endif
        </div>
    </section>

    {{-- ================= INCLUYE + PACKS ================= --}}
    <section class="im-incluye">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <span class="im-antetitulo">Todos los cursos incluyen</span>
                    <h2>Todo lo que necesita para aprender a su ritmo</h2>
                    <ul class="im-check">
                        @foreach (\App\Models\Ajuste::lineas(config('cursos.incluye')) as $i)
                            <li><i class="bx bx-check"></i> {{ $i }}</li>
                        @endforeach
                        <li><i class="bx bx-check"></i> Envío a su correo mediante enlace de descarga</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="im-packs">
                        <i class="bx bx-gift"></i>
                        <div>
                            <h3>¿Varios cursos? Ahorre con un pack</h3>
                            <p>Al comprar grupos de cursos usted ahorra más. Pídanos la lista con los descuentos aplicados.</p>
                            <a class="im-btn im-btn-wa" href="https://wa.me/{{ preg_replace('/\D/', '', config('cursos.whatsapp')) }}?text={{ rawurlencode('Hola InterMet, quisiera la lista de cursos digitales con los descuentos por pack.') }}" target="_blank" rel="noopener"><i class="bx bxl-whatsapp"></i> Pedir la lista con descuentos</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
