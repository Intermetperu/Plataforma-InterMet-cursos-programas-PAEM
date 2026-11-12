<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('descripcion', config('cursos.eslogan') . ' · ' . config('cursos.nombre'))">
    <meta property="og:title" content="@yield('title', 'Cursos digitales') | {{ config('cursos.nombre') }}">
    <meta property="og:description" content="@yield('descripcion', config('cursos.eslogan'))">
    @hasSection('og_imagen')<meta property="og:image" content="@yield('og_imagen')">@endif

    <!-- Plantilla (la misma del sistema del hotel): Bootstrap 5, iconos y menú móvil -->
    <link rel="stylesheet" href="{{ asset('assets/principal/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/principal/css/boxicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/principal/css/flaticon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/principal/css/meanmenu.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/principal/css/animate.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/principal/css/magnific-popup.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/principal/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/principal/css/responsive.css') }}">
    <!-- Estilos y scripts propios -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <title>@yield('title', 'Cursos digitales') | {{ config('cursos.nombre') }}</title>
</head>

@php
    $wa = preg_replace('/\D/', '', (string) config('cursos.whatsapp'));
    $waTexto = rawurlencode('Hola InterMet, tengo una consulta sobre los cursos digitales.');
    $yo = auth()->user();
    $volver = request()->routeIs('login', 'registro') ? null : '/' . ltrim(request()->path(), '/');
@endphp

<body class="im-publico">
    <!-- Barra superior -->
    <div class="im-topbar">
        <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap gap-3">
                <a href="mailto:{{ config('cursos.correo_pedidos') }}"><i class="bx bx-envelope"></i> {{ config('cursos.correo_pedidos') }}</a>
                <a href="https://wa.me/{{ $wa }}?text={{ $waTexto }}" target="_blank" rel="noopener" class="d-none d-sm-inline"><i class="bx bxl-whatsapp"></i> {{ config('cursos.telefono') }}</a>
                <span class="d-none d-lg-inline"><i class="bx bx-time-five"></i> {{ config('cursos.horario') }}</span>
            </div>
            <div class="im-topbar-cuenta">
                @guest
                    <a href="{{ route('login', ['volver' => $volver]) }}"><i class="bx bx-user"></i> Iniciar sesión</a>
                    <span aria-hidden="true">|</span>
                    <a href="{{ route('registro', ['volver' => $volver]) }}">Registrarse</a>
                @else
                    <span><i class="bx bx-user-circle"></i> {{ \Illuminate\Support\Str::limit(strtok($yo->name, ' '), 18) }}</span>
                    <span aria-hidden="true">|</span>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">@csrf<button type="submit">Cerrar sesión</button></form>
                @endguest
            </div>
        </div>
    </div>

    <!-- Menú (plantilla Ecorik) -->
    <div class="eorik-nav-style im-nav">
        <div class="navbar-area">
            @php $enCarrito = \App\Support\Carrito::cantidad(); @endphp
            <div class="mobile-nav">
                <a href="{{ route('home') }}" class="logo"><img src="{{ asset('assets/img/logo-intermet.png') }}" alt="{{ config('cursos.nombre') }}" class="im-logo im-logo-movil"></a>
                <a href="{{ route('carrito.index') }}" class="im-carrito-btn im-carrito-movil" data-carrito-btn aria-label="Mi carrito"><i class="bx bx-cart"></i><span class="im-carrito-num" data-carrito-num @if (! $enCarrito) hidden @endif>{{ $enCarrito }}</span></a>
            </div>
            <div class="main-nav">
                <nav class="navbar navbar-expand-md navbar-light">
                    <div class="container">
                        <a class="navbar-brand" href="{{ route('home') }}"><img src="{{ asset('assets/img/logo-intermet.png') }}" alt="{{ config('cursos.nombre') }}" class="im-logo"></a>
                        <div class="collapse navbar-collapse mean-menu" id="navbarSupportedContent">
                            <ul class="navbar-nav m-auto">
                                <li class="nav-item"><a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home', 'cursos.*') ? 'active' : '' }}">Cursos digitales</a></li>
                                <li class="nav-item"><a href="{{ route('programas.index') }}" class="nav-link {{ request()->routeIs('programas.*') ? 'active' : '' }}">Programas</a></li>
                                <li class="nav-item"><a href="{{ route('home') }}#como-comprar" class="nav-link">Cómo comprar</a></li>
                                <li class="nav-item"><a href="{{ route('pedido.create') }}" class="nav-link {{ request()->routeIs('pedido.create') ? 'active' : '' }}">Registrar pago</a></li>
                                @if ($yo && ! $yo->esPersonal())
                                    <li class="nav-item"><a href="{{ route('pedido.mios') }}" class="nav-link {{ request()->routeIs('pedido.mios', 'pedido.estado') ? 'active' : '' }}">Mis pedidos</a></li>
                                @else
                                    <li class="nav-item"><a href="{{ route('pedido.consulta') }}" class="nav-link {{ request()->routeIs('pedido.consulta', 'pedido.estado') ? 'active' : '' }}">Mi pedido</a></li>
                                @endif
                                <li class="nav-item"><a href="#contacto" class="nav-link">Contacto</a></li>
                                @guest
                                    <li class="nav-item d-lg-none"><a href="{{ route('login', ['volver' => $volver]) }}" class="nav-link">Iniciar sesión</a></li>
                                    <li class="nav-item d-lg-none"><a href="{{ route('registro', ['volver' => $volver]) }}" class="nav-link">Registrarse</a></li>
                                @else
                                    @if ($yo->esPersonal())
                                        <li class="nav-item d-lg-none"><a href="{{ route('admin.dashboard') }}" class="nav-link">Panel de administración</a></li>
                                    @endif
                                @endguest
                            </ul>
                            <div class="others-option im-cuenta-nav">
                                <a href="{{ route('carrito.index') }}" class="im-carrito-btn" data-carrito-btn title="Mi carrito" aria-label="Mi carrito"><i class="bx bx-cart"></i><span class="im-carrito-num" data-carrito-num @if (! $enCarrito) hidden @endif>{{ $enCarrito }}</span></a>
                                @guest
                                    <a href="{{ route('login', ['volver' => $volver]) }}" class="im-btn im-btn-borde"><i class="bx bx-log-in"></i> Iniciar sesión</a>
                                    <a href="{{ route('registro', ['volver' => $volver]) }}" class="im-btn im-btn-primario"><i class="bx bx-user-plus"></i> Registrarse</a>
                                @else
                                    @if ($yo->esPersonal())
                                        <a href="{{ route('admin.dashboard') }}" class="im-btn im-btn-oscuro"><i class="bx bx-grid-alt"></i> Panel</a>
                                    @else
                                        <a href="{{ route('pedido.mios') }}" class="im-usuario" title="Mis pedidos"><span class="im-usuario-ini">{{ mb_strtoupper(mb_substr($yo->name, 0, 1)) }}</span> {{ \Illuminate\Support\Str::limit(strtok($yo->name, ' '), 14) }}</a>
                                    @endif
                                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="im-btn im-btn-borde" title="Cerrar sesión"><i class="bx bx-log-out"></i> Salir</button></form>
                                @endguest
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>

    <main>
        @if (session('aviso'))
            <div class="im-aviso" role="status" data-aviso><i class="bx bx-check-circle"></i><span>{{ session('aviso') }}</span><button type="button" aria-label="Cerrar" data-cerrar-aviso><i class="bx bx-x"></i></button></div>
        @endif
        @yield('content')
    </main>

    <!-- Pie de página -->
    <footer class="im-footer" id="contacto">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <img src="{{ asset('assets/img/logo-intermet-blanco.png') }}" alt="{{ config('cursos.nombre') }}" class="im-logo-pie mb-3">
                    <p>{{ \Illuminate\Support\Str::limit(config('cursos.descripcion'), 220) }}</p>
                    <ul class="im-redes">
                        @foreach (['facebook' => 'bxl-facebook', 'linkedin' => 'bxl-linkedin', 'youtube' => 'bxl-youtube'] as $red => $icono)
                            @if (config("cursos.$red"))
                                <li><a href="{{ config("cursos.$red") }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($red) }}"><i class="bx {{ $icono }}"></i></a></li>
                            @endif
                        @endforeach
                        <li><a href="https://wa.me/{{ $wa }}?text={{ $waTexto }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bx bxl-whatsapp"></i></a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-6" id="como-comprar">
                    <h3>Compra en 3 pasos</h3>
                    <ol class="im-footer-pasos">
                        <li>Elige tu curso y paga con tarjeta en su link de pago.</li>
                        <li><a href="{{ route('pedido.create') }}">Registra tu pago</a> y adjunta la constancia.</li>
                        <li>Recibe los enlaces de descarga en tu correo.</li>
                    </ol>
                    <a href="{{ route('pedido.consulta') }}" class="im-footer-link"><i class="bx bx-search-alt"></i> Consultar mi pedido</a>
                </div>
                <div class="col-lg-4 col-md-12">
                    <h3>Contacto</h3>
                    <ul class="im-footer-contacto">
                        <li><i class="bx bx-envelope"></i><div><span>Pedidos</span><a href="mailto:{{ config('cursos.correo_pedidos') }}">{{ config('cursos.correo_pedidos') }}</a></div></li>
                        <li><i class="bx bxl-whatsapp"></i><div><span>WhatsApp</span><a href="https://wa.me/{{ $wa }}?text={{ $waTexto }}" target="_blank" rel="noopener">{{ config('cursos.telefono') }}</a></div></li>
                        <li><i class="bx bx-time-five"></i><div><span>Horario de atención</span>{{ config('cursos.horario') }}</div></li>
                    </ul>
                </div>
            </div>
            <div class="im-footer-bajo">
                <p class="mb-0">© {{ date('Y') }} {{ config('cursos.nombre') }}. Todos los derechos reservados.</p>
                <p class="mb-0">Los precios para empresas varían: <a href="mailto:{{ config('cursos.correo_informes') }}">consúltanos</a>.</p>
            </div>
        </div>
    </footer>

    <!-- Asistente (chat en el panel lateral) -->
    @if ((string) config('cursos.chatbot_activo') === '1')
        @include('partials.public.chatbot')
    @endif

    <!-- WhatsApp flotante -->
    <a class="im-wa-flotante" href="https://wa.me/{{ $wa }}?text={{ $waTexto }}" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp">
        <i class="bx bxl-whatsapp"></i><span>¿Dudas? Escríbenos</span>
    </a>

    <div class="go-top"><i class='bx bx-chevrons-up bx-fade-up'></i><i class='bx bx-chevrons-up bx-fade-up'></i></div>

    <!-- Scripts de la plantilla -->
    <script src="{{ asset('assets/principal/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/principal/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/principal/js/meanmenu.min.js') }}"></script>
    <script src="{{ asset('assets/principal/js/magnific-popup.min.js') }}"></script>
    <script>
        // Menú móvil y botón «subir» de la plantilla (sin el resto de efectos)
        jQuery(function ($) {
            $('.mean-menu').meanmenu({ meanScreenWidth: '991' });
            $(window).on('scroll', function () { $('.go-top').toggleClass('active', $(this).scrollTop() > 600); });
            $('.go-top').on('click', function () { $('html, body').animate({ scrollTop: 0 }, 400); });
        });
    </script>
    @stack('scripts')
</body>

</html>
