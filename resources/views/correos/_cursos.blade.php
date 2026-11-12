<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e3e9f2;border-radius:10px;margin:14px 0">
    @foreach ($pedido->items as $item)
        <tr>
            <td style="padding:10px 12px;border-bottom:1px solid #e3e9f2;font-size:14px"><b style="color:#0b5ed7">{{ $item->numero ? '#' . $item->numero : '' }}</b> {{ $item->titulo }}</td>
            <td style="padding:10px 12px;border-bottom:1px solid #e3e9f2;font-size:14px;text-align:right;white-space:nowrap">USD {{ number_format((float) $item->precio, 2) }}</td>
        </tr>
    @endforeach
    <tr>
        <td style="padding:10px 12px;font-size:14px"><b>Total</b></td>
        <td style="padding:10px 12px;font-size:15px;text-align:right"><b>USD {{ number_format((float) $pedido->total, 2) }}</b></td>
    </tr>
</table>
