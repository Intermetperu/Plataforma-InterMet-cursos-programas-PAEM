@extends('layouts.public')

@section('title', 'Programas de alta especialización')
@section('descripcion', $programas->count() . ' programas de alta especialización minera: flotación, molienda, relaves, muestreo, hidrometalurgia y más. Varios cursos digitales con certificado a un precio especial.')

@php
    $normal = fn ($t) => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($t));
    $maxAhorro = $programas->max('ahorro');
@endphp

@section('content')
    {{-- ================= CABECERA ================= --}}
    <section class="im-paem-hero">
        <div class="container">
            <nav aria-label="Ruta" class="im-ruta claro">
                <a href="{{ route('home') }}">Inicio</a><i class="bx bx-chevron-right"></i><span>Programas de alta especialización</span>
            </nav>
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="im-paem-sello"><i class="bx bx-award"></i> Programas de Alta Especialización Minera · PAEM</span>
                    <h1>Programas de alta especialización</h1>
                    <p>Cada programa reúne los mejores cursos y congresos digitales de un tema, con <b>certificado digital InterMet</b> y a un precio mucho menor que comprarlos por separado.</p>
                    <form class="im-paem-buscar" role="search" data-paem-buscar-form>
                        <i class="bx bx-search"></i>
                        <input type="search" placeholder="¿Qué quieres aprender? Ej.: flotación, relaves, cobre, Pitard…" aria-label="Buscar un programa" data-paem-buscar autocomplete="off">
                        <button type="button" class="im-paem-limpiar" data-paem-limpiar hidden aria-label="Borrar búsqueda"><i class="bx bx-x"></i></button>
                    </form>
                </div>
                <div class="col-lg-5">
                    <div class="im-paem-cifras">
                        <div><b>{{ $programas->count() }}</b><span>programas</span></div>
                        <div><b>{{ $totalCursos }}</b><span>cursos incluidos</span></div>
                        <div><b>{{ $maxAhorro }}%</b><span>de ahorro máximo</span></div>
                        <div><b><i class="bx bx-award"></i></b><span>certificado digital</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= LISTADO ================= --}}
    <section class="im-seccion pt-4" id="listado">
        <div class="container">
            <div class="im-paem-barra">
                <div class="im-paem-areas" role="group" aria-label="Filtrar por área">
                    <button type="button" class="im-paem-area activa" data-paem-area="">Todos <span>{{ $programas->count() }}</span></button>
                    @foreach ($areas as $clave => [$nombre, $icono, $cuenta])
                        <button type="button" class="im-paem-area" data-paem-area="{{ $clave }}"><i class="bx {{ $icono }}"></i> {{ $nombre }} <span>{{ $cuenta }}</span></button>
                    @endforeach
                </div>
                <div class="im-paem-orden">
                    <span class="im-paem-total" data-paem-total>{{ $programas->count() }} programas</span>
                    <label class="visually-hidden" for="paem-orden">Ordenar</label>
                    <select id="paem-orden" class="form-select" data-paem-orden>
                        <option value="">Recomendados</option>
                        <option value="precio-asc">Precio: menor a mayor</option>
                        <option value="precio-desc">Precio: mayor a menor</option>
                        <option value="cursos">Más cursos incluidos</option>
                        <option value="ahorro">Mayor ahorro</option>
                    </select>
                </div>
            </div>

            <div class="im-paem-grid" data-paem-grid>
                @foreach ($programas as $i => $prog)
                    <div class="im-paem-item" data-paem-item
                         data-area="{{ $prog->area }}" data-orden="{{ $i }}" data-precio="{{ (float) $prog->precio }}"
                         data-cursos="{{ $prog->cursos->count() }}" data-ahorro="{{ $prog->ahorro }}"
                         data-texto="{{ $normal($prog->titulo . ' ' . $prog->nombre . ' ' . $prog->subtitulo . ' ' . (\App\Models\Programa::AREAS[$prog->area][0] ?? '') . ' ' . $prog->cursos->map(fn ($c) => $c->titulo . ' ' . $c->instructor)->implode(' ')) }}">
                        @include('programas._tarjeta', ['prog' => $prog])
                        @if ($prog->ahorro > 0)<span class="im-paem-off">{{ $prog->ahorro }}% OFF</span>@endif
                    </div>
                @endforeach
            </div>

            <div class="im-paem-vacio" data-paem-vacio hidden>
                <i class="bx bx-search-alt"></i>
                <h2>No encontramos programas con «<span data-paem-palabra></span>»</h2>
                <p>Prueba con otra palabra o mira todos los programas.</p>
                <button type="button" class="im-btn im-btn-primario" data-paem-todos>Ver los {{ $programas->count() }} programas</button>
            </div>

            {{-- Cómo funciona --}}
            <div class="im-paem-como">
                <div><i class="bx bx-search-alt"></i><b>1. Elige tu programa</b><span>Revisa los cursos que incluye cada uno.</span></div>
                <div><i class="bx bx-cart-add"></i><b>2. Agrégalo al carrito</b><span>Puedes llevar varios programas a la vez.</span></div>
                <div><i class="bx bx-credit-card"></i><b>3. Paga con tu link</b><span>Pago seguro con tarjeta y registra tu pago.</span></div>
                <div><i class="bx bx-award"></i><b>4. Recibe todo</b><span>Cursos y certificado digital en tu correo.</span></div>
            </div>
        </div>
    </section>
@endsection
