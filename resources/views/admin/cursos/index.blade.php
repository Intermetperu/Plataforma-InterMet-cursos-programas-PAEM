@extends('layouts.admin')

@section('title', 'Cursos')
@section('seccion', 'Catálogo')

@section('acciones')
    <a href="{{ route('admin.cursos.importar') }}" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-file-excel me-1"></i> Importar / exportar Excel</a>
    <a href="{{ route('admin.cursos.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Nuevo curso</a>
@endsection

@php
    $filtros = ['' => ['Todos', 'todos', 'secondary'], 'con_link' => ['Con link de pago', 'con_link', 'success'], 'sin_link' => ['Sin link de pago', 'sin_link', 'danger'], 'sin_video' => ['Sin video', 'sin_video', 'warning'],
        'sin_temario' => ['Sin temario', 'sin_temario', 'warning'], 'oferta' => ['En oferta', 'oferta', 'success'], 'ocultos' => ['Ocultos', 'ocultos', 'dark']];
@endphp

@section('content')
    <div class="card radius-10">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                <ul class="nav nav-pills gap-1">
                    @foreach ($filtros as $k => [$texto, $cuenta, $color])
                        <li class="nav-item"><a class="nav-link {{ (string) $filtro === (string) $k ? 'active' : '' }} py-1 px-2" href="{{ route('admin.cursos.index', array_filter(['filtro' => $k, 'q' => $q, 'anio' => request('anio')])) }}">{{ $texto }} <span class="badge bg-{{ $color }}">{{ $conteo[$cuenta] }}</span></a></li>
                    @endforeach
                </ul>
                <form method="GET" class="d-flex gap-2" role="search">
                    @if ($filtro)<input type="hidden" name="filtro" value="{{ $filtro }}">@endif
                    <select name="anio" class="form-select" style="width: auto" onchange="this.form.submit()" aria-label="Año">
                        <option value="">Todos los años</option>
                        @foreach ($anios as $a)<option value="{{ $a }}" @selected((string) request('anio') === (string) $a)>{{ $a }}</option>@endforeach
                    </select>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Título, instructor o N°" aria-label="Buscar curso">
                    <button class="btn btn-outline-primary" type="submit" aria-label="Buscar"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>N°</th><th></th><th>Curso</th><th>Año</th><th class="text-end">Precio</th><th class="text-center">Link de pago</th><th class="text-center">Video</th><th class="text-center">Vendidos</th><th class="text-center">En la web</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($cursos as $c)
                            @php [$tn, $tc, $ti] = $c->tema; @endphp
                            <tr class="{{ $c->publicado ? '' : 'text-secondary' }}">
                                <td><b>{{ $c->numero_texto }}</b></td>
                                <td>@if ($c->imagen_url)<img src="{{ $c->imagen_url }}" alt="" class="adm-curso-mini">@else<span class="adm-portada-mini im-t-{{ $tc }}"><i class="bx {{ $ti }}"></i></span>@endif</td>
                                <td style="min-width: 280px">
                                    <a href="{{ route('admin.cursos.edit', $c) }}" class="fw-bold">{{ $c->titulo }}</a>
                                    @if ($c->idioma === 'en')<span class="badge bg-info text-dark">EN</span>@endif
                                    @if ($c->tipo === 'congreso')<span class="badge bg-light-primary text-primary">Congreso</span>@endif
                                    <br><small class="text-secondary">{{ $c->instructor }}</small>
                                </td>
                                <td>{{ $c->anio ?? '—' }}</td>
                                <td class="text-end text-nowrap">@usd($c->precio_actual)@if ($c->en_oferta)<br><small class="text-secondary text-decoration-line-through">@usd($c->precio)</small>@endif</td>
                                <td class="text-center">@if ($c->link_pago)<a href="{{ $c->link_pago }}" target="_blank" rel="noopener" title="{{ $c->link_pago }}"><i class="fa-solid fa-circle-check text-success"></i></a>@else<i class="fa-solid fa-circle-xmark text-danger" title="Falta el link de pago"></i>@endif</td>
                                <td class="text-center">@if ($c->youtube_id)<i class="fa-brands fa-youtube text-danger"></i>@else<span class="text-secondary">—</span>@endif</td>
                                <td class="text-center">{{ $c->vendidos ?: '—' }}</td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('admin.cursos.publicar', $c) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $c->publicado ? 'btn-success' : 'btn-outline-secondary' }}" title="{{ $c->publicado ? 'Visible · clic para ocultar' : 'Oculto · clic para publicar' }}"><i class="fa-solid {{ $c->publicado ? 'fa-eye' : 'fa-eye-slash' }}"></i></button>
                                    </form>
                                </td>
                                <td class="text-nowrap text-end">
                                    <a href="{{ $c->url }}" target="_blank" class="btn btn-sm btn-light" title="Ver en la web"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                    <a href="{{ route('admin.cursos.edit', $c) }}" class="btn btn-sm btn-inverse-primary" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-secondary py-4">No hay cursos con estos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $cursos->links() }}
        </div>
    </div>
@endsection
