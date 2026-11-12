@extends('layouts.public')

@section('title', 'Mis pedidos')

@section('content')
    <section class="im-cabecera-simple">
        <div class="container">
            <nav aria-label="Ruta" class="im-migas"><a href="{{ route('home') }}">Cursos digitales</a><i class="bx bx-chevron-right"></i><span>Mis pedidos</span></nav>
            <h1>Mis pedidos</h1>
            <p>{{ $usuario->name }} · {{ $usuario->email }}</p>
        </div>
    </section>

    <section class="im-sobre">
        <div class="container" style="max-width: 900px">
            @if ($pedidos->isEmpty())
                <div class="im-form-bloque text-center py-5">
                    <i class="bx bx-cart-alt" style="font-size: 46px; color: var(--im-azul)"></i>
                    <h2 class="justify-content-center mt-2">Todavía no tiene pedidos</h2>
                    <p class="text-muted">Elija su curso, toque «Comprar ahora» y después registre su pago.</p>
                    <a href="{{ route('home') }}#catalogo" class="im-btn im-btn-primario"><i class="bx bx-book-open"></i> Ver cursos</a>
                </div>
            @else
                @foreach ($pedidos as $p)
                    <a href="{{ $p->url_estado }}" class="im-form-bloque im-mi-pedido">
                        <div class="im-mi-pedido-cab">
                            <b>{{ $p->codigo }}</b>
                            <span class="im-estado-chip {{ $p->estado }}">{{ $p->estado_texto }}</span>
                        </div>
                        <ul>
                            @foreach ($p->items as $i)
                                <li><span>{{ $i->numero ? '#' . str_pad((string) $i->numero, 2, '0', STR_PAD_LEFT) : '' }}</span> {{ $i->titulo }}</li>
                            @endforeach
                        </ul>
                        <div class="im-mi-pedido-pie">
                            <small>{{ $p->created_at->translatedFormat('d \d\e F \d\e Y') }}</small>
                            <b>@usd($p->total)</b>
                            <span class="im-mi-pedido-ver">Ver detalle <i class="bx bx-chevron-right"></i></span>
                        </div>
                    </a>
                @endforeach
            @endif
        </div>
    </section>
@endsection
