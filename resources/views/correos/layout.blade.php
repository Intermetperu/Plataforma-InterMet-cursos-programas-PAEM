{{-- Diseño común de los correos (estilos en línea para que se vean bien en Gmail y Outlook) --}}
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ config('cursos.nombre') }}</title></head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2a3d">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fb;padding:24px 12px">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden">
            <tr><td style="background:#0b2a5b;padding:22px 28px">
                <span style="font-size:22px;font-weight:bold;color:#ffffff">Inter<span style="color:#8fb8ff">Met</span></span>
                <span style="display:block;font-size:11px;letter-spacing:2px;color:#b9c6dc">CURSOS DIGITALES</span>
            </td></tr>
            <tr><td style="padding:28px">
                @yield('cuerpo')
            </td></tr>
            <tr><td style="background:#f4f7fb;padding:18px 28px;font-size:12.5px;color:#6b7a90">
                {{ config('cursos.nombre') }} · {{ config('cursos.correo_pedidos') }} · WhatsApp {{ config('cursos.telefono') }}<br>
                {{ config('cursos.horario') }}
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
