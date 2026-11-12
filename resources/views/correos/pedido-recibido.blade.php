@extends('correos.layout')
@section('cuerpo')
    <h1 style="font-size:22px;color:#0b2a5b;margin:0 0 10px">¡Gracias, {{ \Illuminate\Support\Str::of($pedido->nombre)->before(' ') }}! Recibimos su pago</h1>
    <p style="font-size:15px;line-height:1.5;margin:0 0 6px">Su pedido <b>{{ $pedido->codigo }}</b> quedó registrado. Vamos a verificar el pago y le enviaremos los enlaces de descarga a este correo.</p>
    @include('correos._cursos')
    <p style="font-size:14px;line-height:1.5;margin:0 0 6px">Pago declarado: <b>{{ $pedido->pago_moneda === 'PEN' ? 'S/' : 'USD' }} {{ number_format((float) $pedido->pago_monto, 2) }}</b> · {{ $pedido->metodo_texto }} · operación {{ $pedido->pago_operacion }}</p>
    <p style="margin:22px 0"><a href="{{ $pedido->url_estado }}" style="background:#0b5ed7;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:bold;display:inline-block">Ver el estado de mi pedido</a></p>
    <p style="font-size:13px;color:#6b7a90;margin:0">Guarde este correo: con el código {{ $pedido->codigo }} y su correo puede consultar su pedido en cualquier momento.</p>
@endsection
