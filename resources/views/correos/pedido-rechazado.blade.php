@extends('correos.layout')
@section('cuerpo')
    <h1 style="font-size:21px;color:#0b2a5b;margin:0 0 10px">No pudimos verificar su pago</h1>
    <p style="font-size:15px;line-height:1.5;margin:0 0 10px">Hola {{ \Illuminate\Support\Str::of($pedido->nombre)->before(' ') }}, revisamos su pedido <b>{{ $pedido->codigo }}</b> y no pudimos confirmar el pago.</p>
    <p style="font-size:15px;line-height:1.5;background:#fdecec;border-radius:8px;padding:12px 14px;color:#991b1b"><b>Motivo:</b> {{ $pedido->motivo_rechazo }}</p>
    <p style="font-size:14.5px;line-height:1.5">Si ya pagó, escríbanos por WhatsApp al {{ config('cursos.telefono') }} con su constancia y lo resolvemos de inmediato. También puede <a href="{{ route('pedido.create') }}" style="color:#0b5ed7">registrar su pago de nuevo</a>.</p>
@endsection
