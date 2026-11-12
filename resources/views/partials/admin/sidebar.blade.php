@php
    $menu = [
        ['seccion' => 'Ventas'],
        ['ruta' => 'admin.dashboard', 'permiso' => 'tablero', 'icono' => 'fa-solid fa-gauge', 'texto' => 'Tablero'],
        ['ruta' => 'admin.pedidos.index', 'activo' => 'admin.pedidos.*', 'permiso' => 'pedidos', 'icono' => 'fa-solid fa-receipt', 'texto' => 'Pedidos', 'badge' => $porVerificar],
        ['ruta' => 'admin.reportes', 'permiso' => 'reportes', 'icono' => 'fa-solid fa-chart-column', 'texto' => 'Reportes'],
        ['seccion' => 'Catálogo'],
        ['ruta' => 'admin.cursos.index', 'activo' => ['admin.cursos.index', 'admin.cursos.create', 'admin.cursos.edit'], 'permiso' => 'cursos', 'icono' => 'fa-solid fa-graduation-cap', 'texto' => 'Cursos'],
        ['ruta' => 'admin.programas.index', 'activo' => 'admin.programas.*', 'permiso' => 'cursos', 'icono' => 'fa-solid fa-award', 'texto' => 'Programas (PAEM)'],
        ['ruta' => 'admin.cursos.importar', 'permiso' => 'cursos', 'icono' => 'fa-solid fa-file-excel', 'texto' => 'Importar / exportar Excel'],
        ['seccion' => 'Gestión'],
        ['ruta' => 'admin.usuarios.index', 'permiso' => 'usuarios', 'icono' => 'fa-solid fa-users-gear', 'texto' => 'Usuarios y roles'],
        ['ruta' => 'admin.configuracion', 'icono' => 'fa-solid fa-gear', 'texto' => 'Configuración'],
    ];

    $usuario = auth()->user();
    $menu = collect($menu)->filter(fn ($i) => isset($i['seccion']) || ! isset($i['permiso'])
        || collect((array) $i['permiso'])->contains(fn ($p) => $usuario->puede($p)))->values();
    $menu = $menu->filter(fn ($i, $k) => ! isset($i['seccion']) || (isset($menu[$k + 1]) && ! isset($menu[$k + 1]['seccion'])))->values();
@endphp

<div class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div><img src="{{ asset('assets/img/icono-intermet.png') }}" class="logo-icon" alt="Logo"></div>
        <div><h4 class="logo-text" style="color:#0e3f66;font-weight:700">InterMet</h4></div>
        <div class="toggle-icon ms-auto"><i class='bx bx-arrow-to-left'></i></div>
    </div>
    <ul class="metismenu" id="menu">
        @foreach ($menu as $item)
            @if (isset($item['seccion']))
                <li class="menu-label">{{ $item['seccion'] }}</li>
            @else
                <li class="{{ request()->routeIs($item['activo'] ?? $item['ruta']) ? 'mm-active' : '' }}">
                    <a href="{{ route($item['ruta']) }}">
                        <div class="parent-icon"><i class="{{ $item['icono'] }}"></i></div>
                        <div class="menu-title">{{ $item['texto'] }}</div>
                        @if (! empty($item['badge']))
                            <span class="badge rounded-pill bg-danger menu-badge">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                </li>
            @endif
        @endforeach
    </ul>
</div>
