@extends('layouts.admin')

@section('title', 'Programas de alta especialización')
@section('seccion', 'Catálogo')

@section('acciones')
    <a href="{{ route('home') }}#programas" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Ver en la web</a>
    <a href="{{ route('admin.programas.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Nuevo programa</a>
@endsection

@section('content')
    @php
        $sinLink = $conteo['sin_link'];
        $filtros = ['' => ['Todos', 'secondary'], 'con_link' => ['Con link de pago', 'success'], 'sin_link' => ['Sin link de pago', 'danger'], 'ocultos' => ['Ocultos', 'dark']];
    @endphp
    @if ($sinLink)
        <div class="alert border-0 bg-light-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i> <b>{{ $sinLink }} {{ $sinLink === 1 ? 'programa no tiene' : 'programas no tienen' }} link de pago.</b> Sin link, el cliente igual puede agregarlo al carrito y comprarlo por WhatsApp. Pegue el link en «Editar».</div>
    @endif
    <div class="card radius-10">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                <ul class="nav nav-pills gap-1">
                    @foreach ($filtros as $k => [$texto, $color])
                        <li class="nav-item"><a class="nav-link {{ $filtro === (string) $k ? 'active' : '' }} py-1 px-2" href="{{ route('admin.programas.index', array_filter(['filtro' => $k, 'q' => $q])) }}">{{ $texto }} <span class="badge bg-{{ $color }}">{{ $conteo[$k] }}</span></a></li>
                    @endforeach
                </ul>
                <form method="GET" class="d-flex gap-2" role="search">
                    @if ($filtro)<input type="hidden" name="filtro" value="{{ $filtro }}">@endif
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar programa…" aria-label="Buscar programa">
                    <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>
            @if ($programas->isEmpty())
                <p class="text-center text-secondary py-4 mb-0">No hay programas con este filtro.</p>
            @endif
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>N°</th><th>Programa</th><th>Cursos</th><th class="text-end d-none d-xxl-table-cell">Valor por separado</th><th class="text-end">Precio</th><th>Link</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($programas as $p)
                            <tr>
                                <td>{{ $p->numero }}</td>
                                <td><b>{{ $p->titulo }}</b><div class="font-12 text-secondary"><i class="bx {{ $p->icono }}"></i> {{ $p->nombre }} · {{ $p->subtitulo }} · {{ \App\Models\Programa::AREAS[$p->area][0] ?? 'Sin área' }}</div></td>
                                <td class="font-13">{{ $p->cursos->count() }} <span class="text-secondary">({{ \Illuminate\Support\Str::limit($p->cursos->pluck('numero')->implode(', '), 24) }})</span></td>
                                <td class="text-end text-secondary d-none d-xxl-table-cell">@if ($p->valor_separado)@usd($p->valor_separado)@else — @endif</td>
                                <td class="text-end"><b>@usd($p->precio)</b>@if ($p->ahorro)<div class="font-12 text-success">{{ $p->ahorro }}% OFF</div>@endif</td>
                                <td>@if ($p->link_pago)<span class="badge bg-success">Sí</span>@else<span class="badge bg-danger">Falta</span>@endif</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.programas.publicar', $p) }}">@csrf
                                        <button class="btn btn-sm {{ $p->publicado ? 'btn-light-success' : 'btn-light-secondary' }} border-0" title="Mostrar u ocultar en la web">{!! $p->publicado ? '<i class="fa-solid fa-eye"></i> Visible' : '<i class="fa-solid fa-eye-slash"></i> Oculto' !!}</button>
                                    </form>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ $p->url }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Ver en la web"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                    <a href="{{ route('admin.programas.edit', $p) }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-pen"></i> Editar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
