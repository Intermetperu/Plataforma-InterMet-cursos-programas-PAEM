@extends('layouts.public')

@section('title', $curso->titulo)
@section('descripcion', \Illuminate\Support\Str::limit($curso->resumen ?: $curso->titulo . ' · ' . ($curso->instructor ?? 'InterMet'), 155))
@if ($curso->imagen_url)
    @section('og_imagen', $curso->imagen_url)
@endif

@php
    $wa = preg_replace('/\D/', '', (string) config('cursos.whatsapp'));
    $waCurso = rawurlencode("Hola InterMet, tengo una consulta sobre el curso {$curso->numero_texto} {$curso->titulo}.");
    $horas = $curso->horas ? rtrim(rtrim(number_format((float) $curso->horas, 1), '0'), '.') : null;
    [$temaNombre, $temaColor, $temaIcono] = $curso->tema;
@endphp

@section('content')
    <section class="im-cabecera-curso">
        <div class="container">
            <nav aria-label="Ruta" class="im-migas">
                <a href="{{ route('home') }}">Cursos digitales</a>
                <i class="bx bx-chevron-right"></i>
                @if ($curso->anio)<a href="{{ route('home', ['anio' => $curso->anio]) }}#catalogo">{{ $curso->anio }}</a><i class="bx bx-chevron-right"></i>@endif
                <span>{{ $curso->numero_texto ?: 'Curso' }}</span>
            </nav>
            <div class="im-sellos estatico mb-2">
                <span class="im-sello tema">{{ $temaNombre }}</span>
                @if ($curso->es_nuevo)<span class="im-sello nuevo">Nuevo</span>@endif
                @if ($curso->tipo === 'congreso')<span class="im-sello congreso">Congreso</span>@endif
                @if ($curso->idioma === 'en')<span class="im-sello idioma">English</span>@endif
            </div>
            <h1>{{ $curso->titulo }}</h1>
            <ul class="im-datos-curso">
                @if ($curso->numero)<li><i class="bx bx-hash"></i> Curso N° {{ $curso->numero }}</li>@endif
                @if ($curso->instructor)<li><i class="bx bx-user-voice"></i> {{ $curso->instructor }}</li>@endif
                @if ($curso->anio)<li><i class="bx bx-calendar"></i> Realizado en {{ $curso->fecha_publicacion ? $curso->fecha_publicacion->translatedFormat('F \d\e Y') : $curso->anio }}</li>@endif
                @if ($horas)<li><i class="bx bx-time-five"></i> {{ $horas }} horas de video</li>@endif
                <li><i class="bx bx-world"></i> {{ \App\Models\Curso::IDIOMAS[$curso->idioma] ?? 'Español' }}</li>
            </ul>
            @if ($otraVersion)
                <a href="{{ $otraVersion->url }}" class="im-otra-version"><i class="bx bx-transfer-alt"></i> {{ $curso->idioma === 'en' ? 'Ver la versión en español' : 'Also available in English' }}</a>
            @endif
        </div>
    </section>

    <section class="im-seccion pt-4">
        <div class="container">
            <div class="row g-4">
                {{-- ================= CONTENIDO ================= --}}
                <div class="col-lg-8">
                    <div class="im-bloque p-0 overflow-hidden">
                        @if ($curso->youtube_id)
                            {{-- El video se carga recién al darle play (la página abre más rápido) --}}
                            <div class="im-video" data-youtube="{{ $curso->youtube_id }}">
                                <img src="https://i.ytimg.com/vi/{{ $curso->youtube_id }}/hqdefault.jpg" alt="Video de presentación de {{ $curso->titulo }}">
                                <button type="button" class="im-play grande" aria-label="Reproducir el video de presentación"><i class="bx bx-play"></i></button>
                            </div>
                        @elseif ($curso->imagen)
                            <div class="im-media im-media-foto grande">
                                <span class="im-media-fondo" style="background-image: url('{{ $curso->imagen_url }}')"></span>
                                <img src="{{ $curso->imagen_url }}" alt="{{ $curso->titulo }}">
                            </div>
                        @else
                            <div class="im-media im-portada im-t-{{ $temaColor }} grande">
                                <span class="im-portada-icono"><i class="bx {{ $temaIcono }}"></i></span>
                                <span class="im-portada-tema">{{ $temaNombre }}</span>
                                @if ($curso->numero)<span class="im-portada-num">{{ $curso->numero_texto }}</span>@endif
                            </div>
                        @endif
                    </div>

                    @if ($curso->resumen)
                        <div class="im-bloque">
                            <p class="im-resumen mb-0">{{ $curso->resumen }}</p>
                        </div>
                    @endif

                    <div class="im-bloque">
                        <h2 class="im-bloque-titulo"><i class="bx bx-target-lock"></i> Dirigido a</h2>
                        <ul class="im-check dos-columnas">
                            @foreach ($curso->dirigido_lista as $d)
                                <li><i class="bx bx-check"></i> {{ $d }}</li>
                            @endforeach
                        </ul>
                    </div>

                    @if ($curso->instructor)
                        <div class="im-bloque">
                            <h2 class="im-bloque-titulo"><i class="bx bx-user-voice"></i> {{ str_contains(mb_strtolower($curso->instructor), 'expert') || str_contains($curso->instructor, ',') ? 'Expositores' : 'Instructor' }}</h2>
                            <div class="im-instructor">
                                <span class="im-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(preg_replace('/^(Ing|Dr|Dra|MSc|Mg|Lic)\.?\s*/i', '', $curso->instructor), 0, 1)) }}</span>
                                <div>
                                    <h3>{{ $curso->instructor }}</h3>
                                    @if ($curso->instructor_bio)
                                        <p class="mb-0">{{ $curso->instructor_bio }}</p>
                                    @else
                                        <p class="mb-0 text-muted">Experto internacional convocado por InterMet. Escríbanos si desea conocer su trayectoria completa.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="im-bloque">
                        <h2 class="im-bloque-titulo"><i class="bx bx-list-ol"></i> Temario</h2>
                        @if ($curso->temario_lista)
                            <ol class="im-temario">
                                @foreach ($curso->temario_lista as $t)
                                    <li>
                                        <p>{{ $t['texto'] }}</p>
                                        @if ($t['duracion'])<span class="im-duracion"><i class="bx bx-time-five"></i> {{ $t['duracion'] }}</span>@endif
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <div class="im-temario-pedir">
                                <i class="bx bx-file"></i>
                                <p class="mb-0">¿Desea el temario detallado? Se lo enviamos al instante.</p>
                                <a class="im-btn im-btn-wa" href="https://wa.me/{{ $wa }}?text={{ rawurlencode("Hola InterMet, ¿me envían el temario del curso {$curso->numero_texto} {$curso->titulo}?") }}" target="_blank" rel="noopener"><i class="bx bxl-whatsapp"></i> Pedir temario</a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ================= COMPRA ================= --}}
                <div class="col-lg-4">
                    <aside class="im-compra" id="comprar">
                        <div class="im-compra-precio">
                            <span>Inversión</span>
                            <b>@usd($curso->precio_actual)</b>
                            @if ($curso->en_oferta && $curso->precio_oferta)
                                <s>@usd($curso->precio)</s>
                            @endif
                        </div>

                        @if (request()->boolean('pagando'))
                            <div class="im-alerta info" role="status"><i class="bx bx-info-circle"></i><div>Abrimos la página de pago en otra pestaña. <b>Cuando termine de pagar, registre su pago aquí</b> para recibir el curso.</div></div>
                        @endif

                        @if ($curso->link_pago)
                            <a href="{{ $curso->link_pago }}" target="_blank" rel="noopener" class="im-btn im-btn-primario im-btn-comprar w-100" data-comprar="{{ $curso->url }}?pagando=1#comprar"><i class="bx bx-cart-alt"></i> Comprar ahora</a>
                            <p class="im-pago-seguro"><i class="bx bx-lock-alt"></i> Pago seguro con Niubiz · Visa, Mastercard, American Express, Diners</p>
                        @else
                            <div class="im-alerta info" role="status"><i class="bx bx-time-five"></i><div>La compra en línea de este curso estará disponible muy pronto.</div></div>
                        @endif

                        <h3 class="im-compra-titulo">¿Cómo acceder al curso?</h3>
                        <ol class="im-compra-pasos">
                            <li>
                                <b>Pague en línea</b>
                                <span>Toque «Comprar ahora»: se abre el formulario de pago de Niubiz de este curso.</span>
                            </li>
                            <li class="{{ request()->boolean('pagando') ? 'im-paso-resaltado' : '' }}">
                                <b>Registre su pago</b>
                                <span>Complete el formulario y adjunte su constancia de pago.</span>
                                <a href="{{ route('pedido.create', ['curso' => $curso->slug]) }}" class="im-btn im-btn-oscuro w-100"><i class="bx bx-receipt"></i> Registrar mi pago</a>
                            </li>
                            <li>
                                <b>Reciba sus enlaces</b>
                                <span>{{ config('cursos.envio') }}</span>
                            </li>
                        </ol>

                        <div class="im-compra-incluye">
                            <b>Incluye</b>
                            <ul class="im-check">
                                @foreach (\App\Models\Ajuste::lineas(config('cursos.incluye')) as $i)
                                    <li><i class="bx bx-check"></i> {{ $i }}</li>
                                @endforeach
                                @if ($curso->incluye)<li><i class="bx bx-check"></i> {{ $curso->incluye }}</li>@endif
                            </ul>
                            <p class="small text-muted mb-0">El material es grabado: véalo cuando quiera, las veces que quiera.</p>
                        </div>

                        <a class="im-btn im-btn-wa w-100" href="https://wa.me/{{ $wa }}?text={{ $waCurso }}" target="_blank" rel="noopener"><i class="bx bxl-whatsapp"></i> Atención al cliente</a>
                    </aside>
                </div>
            </div>

            @if ($relacionados->isNotEmpty())
                <div class="im-titulo-seccion mt-5">
                    <div><span class="im-antetitulo">Siga aprendiendo</span><h2>También le puede interesar</h2></div>
                </div>
                <div class="row g-4">
                    @foreach ($relacionados as $r)
                        <div class="col-sm-6 col-lg-4">@include('cursos._tarjeta', ['curso' => $r])</div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Barra fija en el celular --}}
    <div class="im-barra-compra">
        <div><small>Inversión</small><b>@usd($curso->precio_actual)</b></div>
        @if ($curso->link_pago)
            <a href="{{ $curso->link_pago }}" target="_blank" rel="noopener" class="im-btn im-btn-primario" data-comprar="{{ $curso->url }}?pagando=1#comprar"><i class="bx bx-cart-alt"></i> Comprar ahora</a>
        @else
            <a href="#comprar" class="im-btn im-btn-primario"><i class="bx bx-info-circle"></i> Ver detalles</a>
        @endif
    </div>
@endsection
