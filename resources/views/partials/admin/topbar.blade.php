<header>
    <div class="topbar d-flex align-items-center">
        <nav class="navbar navbar-expand gap-3">
            <div class="mobile-toggle-menu"><i class='bx bx-menu'></i></div>

            <form class="search-bar flex-grow-1" method="GET" action="{{ auth()->user()->puede('pedidos') ? route('admin.pedidos.index') : route('admin.cursos.index') }}" role="search">
                <div class="position-relative search-bar-box">
                    <input type="search" name="q" class="form-control search-control" placeholder="{{ auth()->user()->puede('pedidos') ? 'Buscar pedido: código, cliente, correo u operación…' : 'Buscar curso…' }}" aria-label="Buscar">
                    <span class="position-absolute top-50 search-show translate-middle-y"><i class='bx bx-search'></i></span>
                </div>
            </form>

            <div class="top-menu ms-auto">
                <ul class="navbar-nav align-items-center gap-2">
                    <li class="nav-item d-none d-md-flex">
                        <a href="{{ route('home') }}" target="_blank" class="badge bg-light-primary text-primary px-3 py-2 font-14"><i class="fa-solid fa-globe me-1"></i> Ver la web</a>
                    </li>
                    @if (auth()->user()->puede('pedidos'))
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="{{ route('admin.pedidos.index') }}" aria-label="{{ $porVerificar }} pagos por verificar">
                                @if ($porVerificar > 0)<span class="alert-count">{{ $porVerificar }}</span>@endif
                                <i class='bx bx-bell'></i>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="user-box dropdown">
                <a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="{{ asset('assets/img/user.png') }}" class="user-img" alt="Usuario">
                    <div class="user-info ps-3">
                        <p class="user-name mb-0">{{ auth()->user()->name }}</p>
                        <p class="designattion mb-0">{{ auth()->user()->nombre_rol }}</p>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('admin.configuracion') }}?tab=clave"><i class="bx bx-key"></i><span>Mi contraseña</span></a></li>
                    <li><a class="dropdown-item" href="{{ route('home') }}" target="_blank"><i class="bx bx-globe"></i><span>Ver página web</span></a></li>
                    <li><div class="dropdown-divider mb-0"></div></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item"><i class='bx bx-log-out-circle'></i><span>Cerrar sesión</span></button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</header>
