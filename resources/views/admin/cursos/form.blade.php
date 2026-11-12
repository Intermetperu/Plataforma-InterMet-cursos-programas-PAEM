@extends('layouts.admin')

@section('title', $curso->exists ? 'Editar curso ' . $curso->numero_texto : 'Nuevo curso')
@section('seccion', 'Catálogo')

@section('acciones')
    @if ($curso->exists)
        <a href="{{ $curso->url }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Ver en la web</a>
    @endif
    <a href="{{ route('admin.cursos.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Volver</a>
@endsection

@section('content')
    <form method="POST" action="{{ $curso->exists ? route('admin.cursos.update', $curso) : route('admin.cursos.store') }}" enctype="multipart/form-data" data-form-curso>
        @csrf
        @if ($curso->exists) @method('PUT') @endif
        <div class="row">
            <div class="col-xl-8">
                <div class="card radius-10">
                    <div class="card-body row g-3">
                        <h6 class="mb-0">Datos del curso</h6>
                        <div class="col-md-2">
                            <label class="form-label" for="c-num">N°</label>
                            <input type="number" class="form-control" id="c-num" name="numero" value="{{ old('numero', $curso->numero) }}" min="1">
                        </div>
                        <div class="col-md-10">
                            <label class="form-label" for="c-titulo">Título</label>
                            <input class="form-control" id="c-titulo" name="titulo" value="{{ old('titulo', $curso->titulo) }}" required maxlength="255">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="c-anio">Año en que salió</label>
                            <input type="number" class="form-control" id="c-anio" name="anio" value="{{ old('anio', $curso->anio) }}" min="1990" max="2100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="c-fecha">Fecha exacta <small class="text-secondary">(opcional)</small></label>
                            <input type="date" class="form-control" id="c-fecha" name="fecha_publicacion" value="{{ old('fecha_publicacion', $curso->fecha_publicacion?->toDateString()) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="c-idioma">Idioma</label>
                            <select class="form-select" id="c-idioma" name="idioma">
                                @foreach (\App\Models\Curso::IDIOMAS as $k => $v)<option value="{{ $k }}" @selected(old('idioma', $curso->idioma) === $k)>{{ $v }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="c-horas">Horas de video</label>
                            <input type="number" step="0.5" class="form-control" id="c-horas" name="horas" value="{{ old('horas', $curso->horas) }}" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="c-instructor">Instructor(es)</label>
                            <input class="form-control" id="c-instructor" name="instructor" value="{{ old('instructor', $curso->instructor) }}" maxlength="255" placeholder="Ing. Pablo Soto">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="c-incluye">Incluye software / planillas</label>
                            <input class="form-control" id="c-incluye" name="incluye" value="{{ old('incluye', $curso->incluye) }}" maxlength="120" placeholder="Planilla Excel, Software METSIM… (vacío si no incluye)">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="c-resumen">Resumen <small class="text-secondary">(1 o 2 frases bajo el video)</small></label>
                            <textarea class="form-control" id="c-resumen" name="resumen" rows="2" maxlength="1000">{{ old('resumen', $curso->resumen) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="c-instructor-bio">Sobre el instructor</label>
                            <textarea class="form-control" id="c-instructor-bio" name="instructor_bio" rows="4" maxlength="5000">{{ old('instructor_bio', $curso->instructor_bio) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="c-dirigido">Dirigido a <small class="text-secondary">(uno por línea)</small></label>
                            <textarea class="form-control" id="c-dirigido" name="dirigido_a" rows="8" placeholder="Si lo deja vacío se usa la lista general de Configuración">{{ old('dirigido_a', $curso->dirigido_a) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="c-temario">Temario <small class="text-secondary">(un tema por línea; «(2 horas)» al final se muestra como duración)</small></label>
                            <textarea class="form-control" id="c-temario" name="temario" rows="8" maxlength="15000">{{ old('temario', $curso->temario) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card radius-10 border-top border-0 border-4 border-primary">
                    <div class="card-body row g-3">
                        <h6 class="mb-0">Precio y pago</h6>
                        <div class="col-6">
                            <label class="form-label" for="c-precio">Precio regular (USD)</label>
                            <input type="number" step="0.01" class="form-control" id="c-precio" name="precio" value="{{ old('precio', $curso->precio) }}" required min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="c-oferta">Precio oferta (USD)</label>
                            <input type="number" step="0.01" class="form-control" id="c-oferta" name="precio_oferta" value="{{ old('precio_oferta', $curso->precio_oferta) }}" min="0">
                            <small class="text-success" data-descuento></small>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="c-en-oferta" name="en_oferta" value="1" @checked(old('en_oferta', $curso->en_oferta))>
                                <label class="form-check-label" for="c-en-oferta">Mostrar el precio de oferta en la web</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="c-link">Link de pago (Niubiz)</label>
                            <input type="url" class="form-control" id="c-link" name="link_pago" value="{{ old('link_pago', $curso->link_pago) }}" placeholder="https://pagolink.niubiz.com.pe/pagoseguro/INTERMET/…">
                            <small class="text-secondary">Sin link, el curso muestra «Pedir link de pago» por WhatsApp.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="c-enlaces">Enlaces de descarga <small class="text-secondary">(privados · uno por línea)</small></label>
                            <textarea class="form-control" id="c-enlaces" name="enlaces_descarga" rows="3" placeholder="https://we.tl/t-… (videos)&#10;https://we.tl/t-… (materiales)">{{ old('enlaces_descarga', $curso->enlaces_descarga) }}</textarea>
                            <small class="text-secondary">No se muestran en la web: se ponen solos cuando envía el acceso de un pedido.</small>
                        </div>
                    </div>
                </div>

                <div class="card radius-10">
                    <div class="card-body row g-3">
                        <h6 class="mb-0">Imagen del curso</h6>
                        <div class="col-12">
                            <label class="form-label" for="c-video">Video de presentación (YouTube)</label>
                            <input class="form-control" id="c-video" name="video_url" value="{{ old('video_url', $curso->video_url) }}" placeholder="https://youtu.be/…">
                            <div class="mt-2" data-vista-video></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="c-imagen">O una foto de portada <small class="text-secondary">(si no hay video)</small></label>
                            <input type="file" class="form-control" id="c-imagen" name="imagen_archivo" accept="image/jpeg,image/png,image/webp">
                            <img src="{{ $curso->imagen ? asset($curso->imagen) : '' }}" alt="Portada" class="img-fluid rounded mt-2" data-vista-imagen @if (! $curso->imagen) hidden @endif>
                            @if ($curso->imagen)
                                <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="quitar_imagen" value="1" id="c-quitar"><label class="form-check-label font-13" for="c-quitar">Quitar la foto</label></div>
                            @endif
                            <small class="text-secondary d-block mt-1">Sin video ni foto, se genera una portada con el color del tema.</small>
                        </div>
                    </div>
                </div>

                <div class="card radius-10">
                    <div class="card-body row g-3">
                        <h6 class="mb-0">Publicación</h6>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="c-publicado" name="publicado" value="1" @checked(old('publicado', $curso->publicado))>
                                <label class="form-check-label" for="c-publicado">Visible en la web</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="c-destacado" name="destacado" value="1" @checked(old('destacado', $curso->destacado))>
                                <label class="form-check-label" for="c-destacado">Destacado</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="c-slug">Dirección web</label>
                            <div class="input-group">
                                <span class="input-group-text font-13">/cursos-digitales/</span>
                                <input class="form-control" id="c-slug" name="slug" value="{{ old('slug', $curso->slug) }}" placeholder="se crea sola">
                            </div>
                        </div>
                        <div class="col-12 d-grid">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar curso</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @if ($curso->exists)
        <form method="POST" action="{{ route('admin.cursos.destroy', $curso) }}" class="text-end" data-confirmar="¿Eliminar el curso {{ $curso->numero_texto }}? Si ya tiene ventas solo se ocultará.">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-link text-danger"><i class="fa-solid fa-trash me-1"></i> Eliminar curso</button>
        </form>
    @endif
@endsection
