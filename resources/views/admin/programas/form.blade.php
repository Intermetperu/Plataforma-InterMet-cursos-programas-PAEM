@extends('layouts.admin')

@section('title', $programa->exists ? 'Editar programa' : 'Nuevo programa')
@section('seccion', 'Programas de alta especialización')

@section('acciones')
    @if ($programa->exists)
        <a href="{{ $programa->url }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Ver en la web</a>
    @endif
@endsection

@section('content')
    <form method="POST" action="{{ $programa->exists ? route('admin.programas.update', $programa) : route('admin.programas.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($programa->exists) @method('PUT') @endif
        @if ($errors->any())
            <div class="alert border-0 bg-light-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card radius-10"><div class="card-body row g-3">
                    <div class="col-md-2"><label class="form-label" for="p-num">N°</label><input type="number" class="form-control" id="p-num" name="numero" value="{{ old('numero', $programa->numero) }}" min="1"></div>
                    <div class="col-md-10"><label class="form-label" for="p-titulo">Título del programa</label><input class="form-control" id="p-titulo" name="titulo" value="{{ old('titulo', $programa->titulo) }}" required maxlength="190" placeholder="Flotación nivel intermedio"><small class="text-secondary">En la tarjeta se lee: «Programa de alta especialización en …»</small></div>
                    <div class="col-md-6"><label class="form-label" for="p-nombre">Nombre en la tarjeta</label><input class="form-control" id="p-nombre" name="nombre" value="{{ old('nombre', $programa->nombre) }}" required maxlength="60" placeholder="Flotación"></div>
                    <div class="col-md-6"><label class="form-label" for="p-sub">Subtítulo en la tarjeta</label><input class="form-control" id="p-sub" name="subtitulo" value="{{ old('subtitulo', $programa->subtitulo) }}" maxlength="80" placeholder="Nivel intermedio"></div>
                    <div class="col-12">
                        <label class="form-label" for="p-cursos">Cursos que incluye (N° del catálogo)</label>
                        <input class="form-control" id="p-cursos" name="cursos" value="{{ old('cursos', $numerosCursos) }}" required placeholder="1, 6, 15, 18, 31">
                        <small class="text-secondary">Separados por comas, en el orden en que se mostrarán.</small>
                    </div>
                    <div class="col-12"><label class="form-label" for="p-desc">Descripción (opcional)</label><textarea class="form-control" id="p-desc" name="descripcion" rows="3" maxlength="2000">{{ old('descripcion', $programa->descripcion) }}</textarea></div>
                </div></div>

                {{-- Temario --}}
                <div class="card radius-10"><div class="card-body">
                    <h6 class="mb-1"><i class="fa-solid fa-list-check text-primary me-1"></i> Temario del programa</h6>
                    <p class="font-13 text-secondary mb-2">Un tema por línea. Para un título de módulo empiece la línea con <code>#</code>. Si lo deja vacío, la web muestra un módulo por cada curso incluido.</p>
                    <textarea class="form-control font-monospace" name="temario" rows="12" maxlength="20000" placeholder="# Módulo 1: Fundamentos de flotación&#10;Química de superficies y reactivos&#10;Cinética de flotación&#10;# Módulo 2: Optimización de circuitos&#10;Balances metalúrgicos&#10;Casos reales en plantas de cobre">{{ old('temario', $programa->temario) }}</textarea>
                </div></div>

                {{-- Miniaturas --}}
                <div class="card radius-10"><div class="card-body">
                    <h6 class="mb-1"><i class="fa-solid fa-images text-primary me-1"></i> Miniaturas (galería de fotos)</h6>
                    <p class="font-13 text-secondary mb-2">Se muestran en la página del programa, después de la portada. Hasta 20 fotos. Cambie el número para ordenarlas.</p>
                    @if ($programa->galeria)
                        <div class="row g-2 mb-3">
                            @foreach ($programa->galeria as $i => $ruta)
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-1 h-100">
                                        <img src="{{ asset($ruta) }}" alt="" class="w-100 rounded" style="aspect-ratio:4/3;object-fit:cover">
                                        <div class="d-flex align-items-center gap-1 mt-1">
                                            <input type="number" class="form-control form-control-sm" style="width:64px" name="orden_miniaturas[{{ md5($ruta) }}]" value="{{ $i + 1 }}" min="1" aria-label="Orden">
                                            <div class="form-check ms-auto mb-0"><input class="form-check-input" type="checkbox" id="q{{ $i }}" name="quitar_miniaturas[]" value="{{ $ruta }}"><label class="form-check-label font-13 text-danger" for="q{{ $i }}">Quitar</label></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <input type="file" class="form-control" name="miniaturas[]" accept="image/*" multiple>
                    <small class="text-secondary">Puede elegir varias fotos a la vez (JPG, PNG o WEBP, hasta 4 MB cada una).</small>
                    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="p-fotos-cursos" name="fotos_cursos" value="1" @checked(old('fotos_cursos', $programa->exists ? $programa->fotos_cursos : true))><label class="form-check-label" for="p-fotos-cursos">Agregar también las fotos de sus cursos a la galería</label></div>
                </div></div>
            </div>
            <div class="col-lg-4">
                <div class="card radius-10"><div class="card-body row g-3">
                    <div class="col-12"><label class="form-label" for="p-precio">Precio (USD)</label><input type="number" step="0.01" class="form-control" id="p-precio" name="precio" value="{{ old('precio', $programa->precio) }}" required min="1"></div>
                    <div class="col-12"><label class="form-label" for="p-link">Link de pago (Niubiz)</label><input type="url" class="form-control" id="p-link" name="link_pago" value="{{ old('link_pago', $programa->link_pago) }}" placeholder="https://…"></div>
                    <div class="col-6"><label class="form-label" for="p-color">Color</label>
                        <select class="form-select" id="p-color" name="color">@foreach (\App\Models\Programa::COLORES as $k => $v)<option value="{{ $k }}" @selected(old('color', $programa->color) === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label" for="p-icono">Ícono</label><input class="form-control" id="p-icono" name="icono" value="{{ old('icono', $programa->icono) }}" required placeholder="bx-water"></div>
                    <div class="col-12"><label class="form-label" for="p-area">Área (filtro en la página de programas)</label>
                        <select class="form-select" id="p-area" name="area"><option value="">Sin área</option>@foreach (\App\Models\Programa::AREAS as $k => [$nombre]) <option value="{{ $k }}" @selected(old('area', $programa->area) === $k)>{{ $nombre }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label" for="p-orden">Orden</label><input type="number" class="form-control" id="p-orden" name="orden" value="{{ old('orden', $programa->orden) }}" min="0"></div>
                    <div class="col-6 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="p-pub" name="publicado" value="1" @checked(old('publicado', $programa->publicado))><label class="form-check-label" for="p-pub">Visible en la web</label></div></div>
                    <div class="col-12">
                        <label class="form-label" for="p-img">Portada (imagen principal)</label>
                        <input type="file" class="form-control" id="p-img" name="imagen" accept="image/*">
                        <small class="text-secondary">Es la primera foto de la galería. Recomendado: 1200 × 900 px.</small>
                        @if ($programa->imagen)
                            <div class="mt-2"><img src="{{ $programa->imagen_url }}" alt="" class="img-fluid rounded" style="max-height:120px"></div>
                            <div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="p-quitar" name="quitar_imagen" value="1"><label class="form-check-label" for="p-quitar">Quitar la imagen</label></div>
                        @endif
                    </div>
                    <div class="col-12"><button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button></div>
                </div></div>
            </div>
        </div>
    </form>
@endsection
