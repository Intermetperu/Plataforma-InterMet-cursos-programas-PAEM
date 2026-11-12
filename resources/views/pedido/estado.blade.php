@extends('layouts.public')

@section('title', 'Pedido ' . $pedido->codigo)

@php
    $hitos = [
        ['bx-receipt', 'Pago registrado', true],
        ['bx-check-shield', 'Pago verificado', in_array($pedido->estado, ['pagado', 'enviado'])],
        ['bx-envelope', 'Enlaces enviados', $pedido->estado === 'enviado'],
    ];
    $actual = collect($hitos)->search(fn ($h) => ! $h[2]);
    $wa = preg_replace('/\D/', '', (string) config('cursos.whatsapp'));
@endphp

@section('content')
    <section class="im-cabecera-simple">
        <div class="container">
            <nav aria-label="Ruta" class="im-migas"><a href="{{ route('home') }}">Cursos digitales</a><i class="bx bx-chevron-right"></i><span>Mi pedido</span></nav>
            <h1>Pedido {{ $pedido->codigo }}</h1>
            <p>Registrado el {{ $pedido->created_at->translatedFormat('d \d\e F \d\e Y, H:i') }} · {{ $pedido->email }}</p>
        </div>
    </section>

    <section class="im-sobre">
        <div class="container" style="max-width: 920px">
            @if (session('ok'))
                <div class="im-alerta ok" role="status"><i class="bx bx-check-circle"></i><div>{{ session('ok') }}</div></div>
            @endif

            <div class="im-estado">
                <div class="im-estado-cab">
                    <div>
                        <span class="im-antetitulo">Estado</span>
                        <h2>{{ \App\Models\Pedido::ESTADOS[$pedido->estado][2] ?? $pedido->estado_texto }}</h2>
                    </div>
                    <span class="badge bg-{{ $pedido->estado_color }} fs-6 px-3 py-2">{{ $pedido->estado_texto }}</span>
                </div>

                @if ($pedido->estado === 'rechazado')
                    <div class="im-alerta error"><i class="bx bx-error-circle"></i>
                        <div><b>Motivo:</b> {{ $pedido->motivo_rechazo }}<br>
                            Si ya pagó, escríbanos por WhatsApp con su constancia y lo resolvemos de inmediato.
                            <div class="mt-2"><a class="im-btn im-btn-wa" href="https://wa.me/{{ $wa }}?text={{ rawurlencode("Hola InterMet, mi pedido {$pedido->codigo} fue rechazado y quisiera revisarlo.") }}" target="_blank" rel="noopener"><i class="bx bxl-whatsapp"></i> Escribir por WhatsApp</a></div>
                        </div>
                    </div>
                @else
                    <div class="im-linea-tiempo" aria-label="Avance del pedido">
                        @foreach ($hitos as $i => [$icono, $texto, $hecho])
                            <div class="im-hito {{ $hecho ? 'hecho' : ($i === $actual ? 'actual' : '') }}"><span><i class="bx {{ $hecho ? 'bx-check' : $icono }}"></i></span>{{ $texto }}</div>
                        @endforeach
                    </div>
                @endif

                @if ($pedido->estado === 'enviado' && $pedido->enlaces)
                    <h3 class="h5 fw-bold mb-2"><i class="bx bx-download"></i> Sus enlaces de descarga</h3>
                    <ul class="im-enlaces mb-3">
                        @foreach ($pedido->enlaces as $e)
                            <li><a href="{{ $e }}" target="_blank" rel="noopener"><i class="bx bx-cloud-download fs-4"></i> {{ $e }}</a></li>
                        @endforeach
                    </ul>
                    @if ($pedido->mensaje_cliente)<p class="im-cuentas mb-3">{!! nl2br(e($pedido->mensaje_cliente)) !!}</p>@endif
                    <p class="small text-muted">También se los enviamos a {{ $pedido->email }}. Descárguelos pronto: algunos enlaces vencen después de unos días.</p>
                @elseif ($pedido->estado === 'por_verificar')
                    <div class="im-alerta info"><i class="bx bx-time-five"></i><div>Estamos verificando su pago en nuestra cuenta. Le escribiremos a <b>{{ $pedido->email }}</b> con los enlaces de descarga (en horario de atención: {{ config('cursos.horario') }}).</div></div>
                @elseif ($pedido->estado === 'pagado')
                    <div class="im-alerta info"><i class="bx bx-package"></i><div>Su pago está confirmado. Estamos preparando los enlaces de descarga y se los enviaremos a <b>{{ $pedido->email }}</b>.</div></div>
                @endif

                <h3 class="h5 fw-bold mt-4 mb-2">Cursos</h3>
                <ul class="im-lineas">
                    @foreach ($pedido->items as $item)
                        <li class="im-linea">
                            <span class="im-linea-num">{{ $item->numero ? '#' . str_pad($item->numero, 2, '0', STR_PAD_LEFT) : '' }}</span>
                            <span class="im-linea-titulo">@if ($item->curso)<a href="{{ $item->curso->url }}">{{ $item->titulo }}</a>@else{{ $item->titulo }}@endif</span>
                            <span class="im-linea-precio">@usd($item->precio)</span>
                        </li>
                    @endforeach
                </ul>
                <div class="row g-3 small">
                    <div class="col-sm-6"><b class="d-block text-dark">Pago declarado</b>{{ $pedido->pago_moneda === 'PEN' ? 'S/' : 'USD' }} {{ number_format((float) $pedido->pago_monto, 2) }} · {{ $pedido->metodo_texto }}</div>
                    <div class="col-sm-6"><b class="d-block text-dark">Operación</b>{{ $pedido->pago_operacion }} · {{ $pedido->pago_fecha?->format('d/m/Y') }}</div>
                </div>
            </div>

            <p class="text-center mt-4 mb-0"><a href="{{ route('home') }}" class="im-btn im-btn-borde"><i class="bx bx-left-arrow-alt"></i> Volver al catálogo</a></p>
        </div>
    </section>
@endsection
