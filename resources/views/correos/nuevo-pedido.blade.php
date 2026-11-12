@extends('correos.layout')
@section('cuerpo')
    <h1 style="font-size:20px;color:#0b2a5b;margin:0 0 10px">Nuevo pago por verificar: {{ $pedido->codigo }}</h1>
    <p style="font-size:14px;line-height:1.6;margin:0">
        <b>{{ $pedido->nombre }}</b> · {{ $pedido->email }} · {{ $pedido->celular }} · {{ $pedido->pais }}<br>
        Pagó <b>{{ $pedido->pago_moneda === 'PEN' ? 'S/' : 'USD' }} {{ number_format((float) $pedido->pago_monto, 2) }}</b> por {{ $pedido->metodo_texto }} el {{ $pedido->pago_fecha?->format('d/m/Y') }} · operación <b>{{ $pedido->pago_operacion }}</b>
    </p>
    @include('correos._cursos')
    @if (! empty($pedido->pago_alertas['avisos']))
        <p style="font-size:13.5px;background:#fff7e6;border-radius:8px;padding:10px 12px;margin:0 0 12px"><b>Revisar:</b> {{ implode(' · ', $pedido->pago_alertas['avisos']) }}</p>
    @endif
    <p style="margin:18px 0"><a href="{{ route('admin.pedidos.show', $pedido) }}" style="background:#0b2a5b;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:bold;display:inline-block">Revisar en el panel</a></p>
@endsection
