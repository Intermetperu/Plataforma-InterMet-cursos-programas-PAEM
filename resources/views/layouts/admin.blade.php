<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png" />
    <!-- Plantilla: plugins -->
    <link href="{{ asset('assets/admin/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/admin/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet" />
    <!-- Cargador -->
    <link href="{{ asset('assets/admin/css/pace.min.css') }}" rel="stylesheet" />
    <script src="{{ asset('assets/admin/js/pace.min.js') }}"></script>
    <!-- Bootstrap 5 + estilos de la plantilla -->
    <link href="{{ asset('assets/admin/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/css/bootstrap-extended.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/css/icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/admin/css/custom.css') }}" rel="stylesheet">
    <!-- Estilos y scripts propios del sistema -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <title>@yield('title') | {{ config('cursos.nombre') }}</title>
</head>

<body>
    <!--wrapper-->
    <div class="wrapper">
        @include('partials.admin.sidebar')
        @include('partials.admin.topbar')

        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <!--breadcrumb-->
                <div class="page-breadcrumb d-flex flex-wrap align-items-center gap-2 mb-3">
                    <div class="breadcrumb-title pe-3">@yield('seccion', 'Panel')</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" aria-label="Inicio"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">@yield('title')</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="ms-auto d-flex flex-wrap gap-2">
                        @yield('acciones')
                    </div>
                </div>
                <!--end breadcrumb-->

                @if (session('ok'))
                    <div class="alert border-0 border-start border-5 border-success alert-dismissible fade show py-2 bg-white">
                        <i class="bx bx-check-circle text-success me-1"></i> {{ session('ok') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert border-0 border-start border-5 border-danger alert-dismissible fade show py-2 bg-white">
                        <i class="bx bx-error-circle text-danger me-1"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                @endif

                @if ($errors->any())
                        <div class="alert border-0 border-start border-5 border-danger alert-dismissible fade show py-2 bg-white">
                            <b><i class="bx bx-error-circle text-danger me-1"></i> Revisa estos datos:</b>
                            <ul class="mb-0 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                @endif

                @yield('content')
            </div>
        </div>
        <!--end page wrapper -->

        <!--start overlay-->
        <div class="overlay toggle-icon"></div>
        <!--end overlay-->
        <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <footer class="page-footer">
            <p class="mb-0">Copyright © {{ date('Y') }} {{ config('cursos.nombre') }}. Todos los derechos reservados.</p>
        </footer>
    </div>
    <!--end wrapper-->

    <!-- Bootstrap JS -->
    <script src="{{ asset('assets/admin/js/bootstrap.bundle.min.js') }}"></script>
    <!-- Plugins -->
    <script src="{{ asset('assets/admin/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/admin/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/admin/plugins/metismenu/js/metisMenu.min.js') }}"></script>
    <!-- App JS de la plantilla + Font Awesome -->
    <script src="{{ asset('assets/admin/js/app.js') }}"></script>
    <script src="{{ asset('assets/admin/js/all.min.js') }}"></script>
    @stack('scripts')
</body>

</html>
