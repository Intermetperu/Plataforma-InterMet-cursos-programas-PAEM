@extends('layouts.public')

@section('title', 'Registrar mi pago')

@php
    $cliente = auth()->user() && ! auth()->user()->esPersonal() ? auth()->user() : null;
    $paises = ['Perú', 'Chile', 'México', 'Colombia', 'Ecuador', 'Bolivia', 'Argentina', 'Brasil', 'Venezuela', 'Panamá', 'Guatemala', 'Honduras', 'Paraguay', 'Uruguay', 'República Dominicana', 'España', 'Estados Unidos', 'Canadá', 'Australia', 'Sudáfrica'];
@endphp

@section('content')
    <section class="im-cabecera-simple">
        <div class="container">
            <nav aria-label="Ruta" class="im-migas"><a href="{{ route('home') }}">Cursos digitales</a><i class="bx bx-chevron-right"></i><span>Registrar pago</span></nav>
            <h1>Registre su pago</h1>
            <p>¿Ya pagó con el link de pago? Indique qué cursos compró y adjunte su constancia. Verificamos el pago y le enviamos los enlaces de descarga a su correo.</p>
        </div>
    </section>

    <section class="im-sobre">
        <div class="container">
            <form method="POST" action="{{ route('pedido.store') }}" enctype="multipart/form-data" data-registro-pago novalidate>
                @csrf
                <input type="text" name="sitio_web" class="im-trampa" tabindex="-1" autocomplete="off" aria-hidden="true">
                <div class="row g-4">
                    <div class="col-lg-8">
                        @if ($errors->any())
                            <div class="im-alerta error" role="alert">
                                <i class="bx bx-error-circle"></i>
                                <div><b>Revise estos datos:</b><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                            </div>
                        @endif

                        {{-- 1. Cursos --}}
                        <div class="im-form-bloque">
                            <h2><span class="im-paso-num">1</span> Cursos o programas que pagó</h2>
                            <ul class="im-lineas" data-lineas></ul>
                            <p class="text-muted mb-3" data-sin-cursos>Todavía no eligió ningún curso ni programa.</p>
                            <label class="form-label" for="agregar-curso">Agregar un curso o programa</label>
                            <div class="im-agregar">
                                <select class="form-select" id="agregar-curso" data-agregar>
                                    <option value="">Busque y elija el curso o programa…</option>
                                    @if ($programas->isNotEmpty())
                                        <optgroup label="Programas de alta especialización">
                                            @foreach ($programas as $p)
                                                <option value="p{{ $p->id }}" data-numero="PAEM" data-precio="{{ $p->precio }}" data-titulo="Programa de alta especialización en {{ \Illuminate\Support\Str::lower($p->titulo) }}">
                                                    Programa · {{ \Illuminate\Support\Str::limit($p->titulo, 80) }} · USD {{ number_format((float) $p->precio) }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @foreach ($todos->groupBy(fn ($c) => $c->anio ?: 'Sin año')->sortKeysDesc() as $anio => $grupo)
                                        <optgroup label="{{ $anio }}">
                                            @foreach ($grupo->sortByDesc('numero') as $c)
                                                <option value="{{ $c->id }}" data-numero="{{ $c->numero_texto }}" data-precio="{{ $c->precio_actual }}" data-titulo="{{ $c->titulo }}{{ $c->idioma === 'en' ? ' (English)' : '' }}">
                                                    {{ $c->numero_texto }} · {{ \Illuminate\Support\Str::limit($c->titulo, 80) }}{{ $c->idioma === 'en' ? ' (EN)' : '' }} · USD {{ number_format($c->precio_actual) }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <button type="button" class="im-btn im-btn-borde" data-agregar-btn><i class="bx bx-plus"></i> Agregar</button>
                            </div>
                            <div data-elegidos hidden>
                                @foreach ($elegidos as $id)<input type="hidden" name="cursos[]" value="{{ $id }}">@endforeach
                                @foreach ($progElegidos as $id)<input type="hidden" name="programas[]" value="{{ $id }}" data-id="p{{ $id }}">@endforeach
                            </div>
                        </div>

                        {{-- 2. Datos --}}
                        <div class="im-form-bloque">
                            <h2><span class="im-paso-num">2</span> Sus datos</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="f-nombre">Nombres y apellidos</label>
                                    <input class="form-control @error('nombre') is-invalid @enderror" id="f-nombre" name="nombre" value="{{ old('nombre', $cliente?->name) }}" required minlength="3" maxlength="120" autocomplete="name">
                                    <div class="form-text">Así saldrá en su certificado.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="f-email">Correo electrónico</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="f-email" name="email" value="{{ old('email', $cliente?->email) }}" required maxlength="150" autocomplete="email">
                                    <div class="form-text">Aquí le enviaremos los enlaces de descarga.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="f-celular">Celular / WhatsApp</label>
                                    <input class="form-control @error('celular') is-invalid @enderror" id="f-celular" name="celular" value="{{ old('celular', $cliente?->celular) }}" required inputmode="tel" maxlength="30" placeholder="+51 987 654 321" autocomplete="tel">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="f-pais">País</label>
                                    <input class="form-control @error('pais') is-invalid @enderror" id="f-pais" name="pais" value="{{ old('pais', $cliente?->pais ?: 'Perú') }}" list="lista-paises" required maxlength="60" autocomplete="country-name">
                                    <datalist id="lista-paises">@foreach ($paises as $p)<option value="{{ $p }}">@endforeach</datalist>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="f-doc">DNI / documento <span class="text-muted fw-normal">(opcional)</span></label>
                                    <input class="form-control" id="f-doc" name="documento" value="{{ old('documento') }}" maxlength="30">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="f-empresa">Empresa <span class="text-muted fw-normal">(opcional)</span></label>
                                    <input class="form-control" id="f-empresa" name="empresa" value="{{ old('empresa') }}" maxlength="150" autocomplete="organization">
                                </div>
                            </div>
                        </div>

                        {{-- 3. Pago --}}
                        <div class="im-form-bloque">
                            <h2><span class="im-paso-num">3</span> Su pago</h2>
                            <input type="hidden" name="pago_metodo" value="niubiz">
                            <input type="hidden" name="pago_moneda" value="USD">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="f-monto">Monto pagado (USD)</label>
                                    <input type="text" inputmode="decimal" class="form-control @error('pago_monto') is-invalid @enderror" id="f-monto" name="pago_monto" value="{{ old('pago_monto') }}" required data-monto>
                                    <button type="button" class="btn btn-link btn-sm p-0 mt-1" data-usar-total>Usar el total del pedido</button>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="f-fecha">Fecha del pago</label>
                                    <input type="date" class="form-control @error('pago_fecha') is-invalid @enderror" id="f-fecha" name="pago_fecha" value="{{ old('pago_fecha', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="f-op">N.º de operación</label>
                                    <input class="form-control @error('pago_operacion') is-invalid @enderror" id="f-op" name="pago_operacion" value="{{ old('pago_operacion') }}" required maxlength="40" placeholder="Sale en el voucher o en el correo de Niubiz">
                                    <div class="form-text">¿Pagó varios cursos por separado? Escriba los números separados por comas.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="f-captura">Constancia del pago</label>
                                    <input type="file" class="form-control @error('captura') is-invalid @enderror" id="f-captura" name="captura" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                                    <div class="form-text">Captura de la pantalla «pago exitoso» o del correo de confirmación (monto, fecha y n.º de operación visibles). JPG, PNG o PDF · máx. 5 MB.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="f-nota">Comentario <span class="text-muted fw-normal">(opcional)</span></label>
                                    <textarea class="form-control" id="f-nota" name="nota" rows="2" maxlength="600" placeholder="Ej.: necesito factura a nombre de mi empresa.">{{ old('nota') }}</textarea>
                                </div>
                            </div>
                            <p class="small text-muted mt-3 mb-0"><i class="bx bx-shield-quarter"></i> Revisamos cada pago en nuestra cuenta antes de enviar los cursos. Constancias repetidas o editadas se rechazan.</p>
                        </div>
                    </div>

                    {{-- Resumen --}}
                    <div class="col-lg-4">
                        <aside class="im-resumen-pedido">
                            <h3>Resumen de su pedido</h3>
                            <ul class="im-resumen-lista" data-resumen></ul>
                            <div class="im-resumen-total">
                                <span>Total</span>
                                <div class="text-end"><b data-total>USD 0</b></div>
                            </div>
                            <button type="submit" class="im-btn im-btn-primario w-100 mt-3"><i class="bx bx-send"></i> Enviar mi pago</button>
                            <p class="small text-muted mt-2 mb-3">{{ config('cursos.envio') }}</p>
                            <p class="small mb-0"><b>¿Aún no pagó?</b> Abra el curso y toque «Comprar ahora»: se abre el formulario de pago de Niubiz.</p>
                        </aside>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
