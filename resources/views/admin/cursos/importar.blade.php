@extends('layouts.admin')

@section('title', 'Importar / exportar Excel')
@section('seccion', 'Catálogo')

@section('content')
    <div class="row">
        <div class="col-xl-7">
            <div class="card radius-10">
                <div class="card-body">
                    <h5 class="mb-1"><i class="fa-solid fa-file-arrow-up text-success me-1"></i> Importar cursos desde Excel</h5>
                    <p class="text-secondary">Suba su Excel con los <b>links de pago</b> (o cualquier dato de los cursos). El sistema reconoce las columnas por su título, en cualquier orden:</p>
                    <div class="d-flex flex-wrap gap-1 mb-3 font-13">
                        @foreach (['N°', 'Título', 'Año', 'Oferta', 'Regular', 'Expositor', 'Horas', 'Link de pago', 'Links de pago 2026', 'Vídeos', 'Materiales', 'Video YouTube', 'Idioma', 'Publicado'] as $col)
                            <span class="badge bg-light text-dark border">{{ $col }}</span>
                        @endforeach
                    </div>
                    <ul class="font-14 text-secondary">
                        <li>Cada curso se reconoce por su <b>N°</b> y su <b>título</b>. Solo se cambian las celdas que tienen datos: las vacías no borran nada.</li>
                        <li>Si en la columna del link escribió «Comprar» con un <b>hipervínculo</b>, se toma el link.</li>
                        <li>Si hay dos columnas de link de pago (por ejemplo «Link de pago» y «Links de pago 2026»), se usa <b>la última</b>.</li>
                        <li>Las columnas <b>Vídeos</b> y <b>Materiales</b> (WeTransfer, pCloud…) se guardan como enlaces de descarga privados del curso.</li>
                        <li>Sirven archivos <b>.xlsx</b>, <b>.csv</b> y <b>.txt</b> (los .xls antiguos: ábralos y guárdelos como .xlsx).</li>
                    </ul>
                    <form method="POST" action="{{ route('admin.cursos.importar.hoja') }}" class="row g-2 mb-4 p-3 rounded bg-light border">
                        @csrf
                        <div class="col-12">
                            <label class="form-label fw-bold" for="hoja"><i class="fa-brands fa-google-drive text-success me-1"></i> Desde Google Sheets <small class="text-secondary fw-normal">(pegue el link de su hoja)</small></label>
                            <input type="url" class="form-control @error('hoja') is-invalid @enderror" id="hoja" name="hoja" value="{{ old('hoja', config('cursos.hoja_cursos')) }}" placeholder="https://docs.google.com/spreadsheets/d/…/edit?usp=sharing" required>
                            @error('hoja')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-secondary">La hoja debe estar compartida como «Cualquier persona con el enlace». Cada vez que agregue links en la hoja, vuelva a darle a «Actualizar».</small>
                        </div>
                        <input type="hidden" name="crear" value="1">
                        <div class="col-12"><button type="submit" class="btn btn-success"><i class="fa-solid fa-rotate me-1"></i> Actualizar desde la hoja</button></div>
                    </form>

                    <h6 class="mb-2">O suba un archivo</h6>
                    <form method="POST" action="{{ route('admin.cursos.importar.store') }}" enctype="multipart/form-data" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label" for="archivo">Archivo</label>
                            <input type="file" class="form-control @error('archivo') is-invalid @enderror" id="archivo" name="archivo" accept=".xlsx,.csv,.txt,.tsv" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="crear" name="crear" value="1" checked>
                                <label class="form-check-label" for="crear">Crear los cursos que no estén en el catálogo (si tienen precio)</label>
                            </div>
                        </div>
                        <div class="col-12"><button type="submit" class="btn btn-success"><i class="fa-solid fa-upload me-1"></i> Importar</button></div>
                    </form>

                    @if ($resumen)
                        <hr>
                        <h6>Resultado</h6>
                        <div class="row row-cols-3 g-2 text-center mb-2">
                            <div class="col"><div class="border rounded p-2"><b class="fs-4 text-primary">{{ $resumen['actualizados'] }}</b><br><small>actualizados</small></div></div>
                            <div class="col"><div class="border rounded p-2"><b class="fs-4 text-success">{{ $resumen['creados'] }}</b><br><small>nuevos</small></div></div>
                            <div class="col"><div class="border rounded p-2"><b class="fs-4 text-secondary">{{ $resumen['sin_cambios'] }}</b><br><small>sin cambios</small></div></div>
                        </div>
                        @if ($resumen['columnas'])
                            <p class="font-13 text-secondary mb-1">Columnas reconocidas: {{ implode(', ', $resumen['columnas']) }}</p>
                        @endif
                        @if ($resumen['omitidos'])
                            <div class="alert bg-light-warning border-0 font-13 mb-0"><b>Revise:</b><ul class="mb-0">@foreach (array_slice($resumen['omitidos'], 0, 30) as $o)<li>{{ $o }}</li>@endforeach</ul></div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card radius-10 border-top border-0 border-4 border-success">
                <div class="card-body">
                    <h5 class="mb-1"><i class="fa-solid fa-file-arrow-down text-success me-1"></i> Exportar el catálogo</h5>
                    <p class="text-secondary">Descargue todos los cursos en Excel, complete lo que falta (link de pago, video, horas…) y vuelva a importarlo aquí.</p>
                    <a href="{{ route('admin.cursos.plantilla') }}" class="btn btn-outline-success w-100"><i class="fa-solid fa-download me-1"></i> Descargar cursos en Excel</a>
                </div>
            </div>

            @php
                $limiteMb = (function ($v) { $n = (float) $v; return match (strtoupper(substr(trim($v), -1))) { 'G' => $n * 1024, 'K' => $n / 1024, 'M' => $n, default => $n / 1048576 }; })((string) ini_get('upload_max_filesize'));
                $limitePost = (function ($v) { $n = (float) $v; return match (strtoupper(substr(trim($v), -1))) { 'G' => $n * 1024, 'K' => $n / 1024, 'M' => $n, default => $n / 1048576 }; })((string) ini_get('post_max_size'));
                $limite = min($limiteMb, $limitePost ?: $limiteMb);
            @endphp
            <div class="card radius-10 border-top border-0 border-4 border-primary">
                <div class="card-body">
                    <h5 class="mb-1"><i class="fa-solid fa-images text-primary me-1"></i> Imágenes de los cursos (ZIP)</h5>
                    <p class="text-secondary mb-2">Suba un ZIP con una imagen por curso. Cada archivo debe empezar con el <b>N°</b> y el <b>idioma</b> del curso:</p>
                    <p class="font-13 mb-2"><code>003-es-dimensionamiento.jpg</code> → curso N° 3 en español<br><code>110-en-sampling-of-gold.jpg</code> → curso N° 110 en inglés</p>
                    <form method="POST" action="{{ route('admin.cursos.importar.imagenes') }}" enctype="multipart/form-data" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <input type="file" class="form-control @error('zip') is-invalid @enderror" name="zip" accept=".zip" required>
                            @error('zip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="reemplazar" name="reemplazar" value="1">
                                <label class="form-check-label" for="reemplazar">Cambiar también las imágenes que los cursos ya tienen</label>
                            </div>
                        </div>
                        <div class="col-12"><button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-upload me-1"></i> Importar imágenes</button></div>
                    </form>
                    @if ($limite < 8)
                        <p class="font-13 text-secondary mt-2 mb-0"><i class="fa-solid fa-circle-info me-1"></i> Su servidor acepta archivos de hasta <b>{{ ini_get('upload_max_filesize') }}</b>. Si el ZIP pesa más, póngalo en la carpeta de la plataforma y haga doble clic en <b>IMPORTAR-IMAGENES.bat</b>.</p>
                    @endif
                    @if ($resumenImagenes)
                        <hr>
                        <div class="row row-cols-2 g-2 text-center mb-2">
                            <div class="col"><div class="border rounded p-2"><b class="fs-4 text-primary">{{ $resumenImagenes['asignadas'] }}</b><br><small>con imagen nueva</small></div></div>
                            <div class="col"><div class="border rounded p-2"><b class="fs-4 text-secondary">{{ $resumenImagenes['ya_tenian'] }}</b><br><small>ya tenían (no se tocaron)</small></div></div>
                        </div>
                        @if ($resumenImagenes['omitidas'])
                            <div class="alert bg-light-warning border-0 font-13 mb-0"><b>Revise:</b><ul class="mb-0">@foreach (array_slice($resumenImagenes['omitidas'], 0, 30) as $o)<li>{{ $o }}</li>@endforeach</ul></div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
