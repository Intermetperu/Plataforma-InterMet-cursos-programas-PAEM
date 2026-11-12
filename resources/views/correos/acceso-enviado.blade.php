@extends('correos.layout')
@section('cuerpo')
    <h1 style="font-size:22px;color:#0b2a5b;margin:0 0 10px">¡Sus cursos están listos, {{ \Illuminate\Support\Str::of($pedido->nombre)->before(' ') }}!</h1>
    <p style="font-size:15px;line-height:1.5;margin:0">Verificamos su pago del pedido <b>{{ $pedido->codigo }}</b>. Estos son sus enlaces de descarga:</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:14px 0">
        @foreach ($pedido->enlaces as $enlace)
            <tr><td style="padding:6px 0"><a href="{{ $enlace }}" style="display:block;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 14px;color:#166534;font-weight:bold;text-decoration:none;word-break:break-all">⬇ {{ $enlace }}</a></td></tr>
        @endforeach
    </table>
    @if ($pedido->mensaje_cliente)
        <p style="font-size:14.5px;line-height:1.5;background:#f4f7fb;border-radius:8px;padding:12px 14px">{!! nl2br(e($pedido->mensaje_cliente)) !!}</p>
    @endif
    @include('correos._cursos')
    <p style="font-size:13.5px;color:#6b7a90;line-height:1.5">Descargue los archivos pronto: algunos enlaces (como WeTransfer) vencen después de unos días. Si un enlace no abre, responda este correo o escríbanos por WhatsApp.</p>
    <p style="margin:18px 0 0"><a href="{{ $pedido->url_estado }}" style="color:#0b5ed7">Ver mi pedido en la web</a></p>
@endsection
