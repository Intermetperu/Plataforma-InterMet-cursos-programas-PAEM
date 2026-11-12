@extends('layouts.public')

@section('title', 'Mi carrito')

@php
    $wa = preg_replace('/\D/', '', (string) config('cursos.whatsapp'));
    $lineas = $programas->map(fn ($p) => '• ' . $p->titulo . ' (USD ' . number_format((float) $p->precio) . ')')->implode("\n");
    $waCarrito = rawurlencode("Hola InterMet, quiero comprar estos programas de alta especialización:\n{$lineas}\nTotal: USD " . number_format($total));
    $sinLink = $programas->whereNull('link_pago')->count();
@endphp

@section('content')
    <section class="im-seccion pt-4">
        <div class="container">
            <nav aria-label="Ruta" class="im-ruta">
                <a href="{{ route('home') }}#programas">Programas de alta especialización</a>
                <i class="bx bx-chevron-right"></i>
                <span>Mi carrito</span>
            </nav>

            @if ($programas->isEmpty())
                <div class="im-producto im-carrito-vacio">
                    <i class="bx bx-cart"></i>
                    <h1 class="h4 fw-bold mt-3">Tu carrito está vacío</h1>
                    <p class="text-muted">Elige uno o varios programas de alta especialización y agrégalos aquí.</p>
                    <a href="{{ route('home') }}#programas" class="im-btn im-btn-primario mt-2"><i class="bx bx-award"></i> Ver los programas</a>
                </div>
            @else
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="im-producto">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h1 class="h4 fw-bold m-0">Mi carrito <span class="text-muted fw-normal">({{ $programas->count() }})</span></h1>
                                <form method="POST" action="{{ route('carrito.vaciar') }}">@csrf @method('DELETE')<button type="submit" class="im-carrito-quitar m-0">Vaciar carrito</button></form>
                            </div>
                            @foreach ($programas as $p)
                                <div class="im-carrito-fila">
                                    <a href="{{ $p->url }}" class="im-programa im-prog-{{ $p->color }}" tabindex="-1" aria-hidden="true">
                                        <span class="im-programa-cab"><span class="im-programa-logo"><i class="bx {{ $p->icono }}"></i><span><b>{{ $p->nombre }}</b><small>{{ $p->subtitulo }}</small></span></span></span>
                                    </a>
                                    <div class="im-carrito-info">
                                        <h3><a href="{{ $p->url }}">Programa de alta especialización en {{ \Illuminate\Support\Str::lower($p->titulo) }}</a></h3>
                                        <p>{{ $p->cursos->count() }} {{ $p->cursos->count() === 1 ? 'curso' : 'cursos' }} · certificado digital incluido</p>
                                        <form method="POST" action="{{ route('carrito.quitar', $p) }}">@csrf @method('DELETE')<button type="submit" class="im-carrito-quitar">Eliminar</button></form>
                                    </div>
                                    <div class="im-carrito-precio">
                                        <div>
                                            @if ($p->ahorro > 0)<s>@usd($p->valor_separado)</s>@endif
                                            <b>@usd($p->precio)</b>
                                        </div>
                                        @if ($p->link_pago)
                                            <a href="{{ $p->link_pago }}" target="_blank" rel="noopener" class="im-btn im-btn-primario" data-pagado><i class="bx bx-credit-card"></i> Pagar</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="im-producto im-caja-compra">
                            <h2 class="h5 fw-bold mb-3">Resumen de compra</h2>
                            <div class="im-resumen-fila"><span>Programas ({{ $programas->count() }})</span><span>@usd($valor)</span></div>
                            @if ($valor > $total)
                                <div class="im-resumen-fila" style="color:#00a650"><span>Ahorro por programa</span><span>− @usd($valor - $total)</span></div>
                            @endif
                            <div class="im-resumen-total"><span>Total</span><span>@usd($total)</span></div>

                            <ol class="im-pasos-carrito">
                                <li><b>Paga cada programa</b> con el botón «Pagar» (link de pago seguro con tarjeta).</li>
                                <li><b>Registra tu pago</b> y adjunta la constancia: ya van marcados los programas de tu carrito.</li>
                                <li><b>Recibe tus cursos</b> y certificados en tu correo.</li>
                            </ol>
                            @if ($sinLink)
                                <p class="small text-muted">{{ $sinLink === 1 ? 'Un programa todavía no tiene' : $sinLink . ' programas todavía no tienen' }} link de pago: escríbenos y te lo enviamos.</p>
                            @endif
                            <a href="{{ route('pedido.create', ['programa' => $programas->pluck('slug')->all()]) }}" class="im-btn im-btn-oscuro"><i class="bx bx-receipt"></i> Ya pagué: registrar mi pago</a>
                            <a href="https://wa.me/{{ $wa }}?text={{ $waCarrito }}" target="_blank" rel="noopener" class="im-btn im-btn-wa"><i class="bx bxl-whatsapp"></i> Comprar por WhatsApp</a>
                            <a href="{{ route('home') }}#programas" class="im-btn im-btn-borde"><i class="bx bx-plus"></i> Agregar más programas</a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
