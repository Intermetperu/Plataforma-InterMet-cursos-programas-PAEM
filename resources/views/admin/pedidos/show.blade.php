@extends('layouts.admin')

@section('title', 'Pedido ' . $pedido->codigo)
@section('seccion', 'Pedidos')

@section('acciones')
    <a href="{{ route('admin.pedidos.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Volver</a>
@endsection

@php
    $esPdf = str_ends_with(strtolower($pedido->pago_captura), '.pdf');
    $alertas = $pedido->pago_alertas['avisos'] ?? [];
    $archivo = $pedido->pago_alertas['archivo'] ?? [];
    $simbolo = $pedido->pago_moneda === 'PEN' ? 'S/' : 'USD';
    $esperado = (float) $pedido->total;
    // Enlaces de descarga guardados en cada curso: se proponen solos al enviar el acceso
    // Un programa (PAEM) entrega todos sus cursos
    $cursosEntregar = $pedido->items->flatMap(fn ($i) => $i->programa ? $i->programa->cursos : collect([$i->curso]))->filter()->unique('id');
    $enlacesCursos = $cursosEntregar->map(fn ($c) => $c->enlaces_descarga)->filter()->implode("\n");
    $sinEnlace = $cursosEntregar->filter(fn ($c) => ! $c->enlaces_descarga);
    $cel = preg_replace('/\D/', '', $pedido->celular);
    if (strlen($cel) === 9) { $cel = '51' . $cel; }
    $waEnlaces = "Hola {$pedido->nombre}, le saluda InterMet. Verificamos su pago del pedido {$pedido->codigo}. Estos son sus enlaces de descarga:\n" . implode("\n", $pedido->enlaces) . "\n¡Gracias por su compra!";
@endphp

@section('content')
    <div class="row">
        <div class="col-xl-8">
            {{-- Cliente y cursos --}}
            <div class="card radius-10">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start gap-2 mb-3">
                        <div>
                            <h5 class="mb-0">{{ $pedido->nombre }}</h5>
                            <p class="text-secondary mb-0">{{ $pedido->email }} · {{ $pedido->celular }} · {{ $pedido->pais }}
                                @if ($pedido->documento) · Doc. {{ $pedido->documento }}@endif
                                @if ($pedido->empresa) · {{ $pedido->empresa }}@endif</p>
                        </div>
                        <span class="badge bg-{{ $pedido->estado_color }} ms-auto fs-6">{{ $pedido->estado_texto }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-2">
                            <thead class="table-light"><tr><th>N°</th><th>Curso</th><th class="text-end">Precio</th></tr></thead>
                            <tbody>
                                @foreach ($pedido->items as $i)
                                    <tr>
                                        <td>{{ $i->numero ? '#' . $i->numero : ($i->programa ? 'PAEM' : '—') }}</td>
                                        <td>{{ $i->titulo }}@if ($i->curso?->idioma === 'en') <span class="badge bg-info text-dark">EN</span>@endif
                                            @if ($i->programa)<div class="font-12 text-secondary"><span class="badge bg-primary">PAEM</span> Incluye: {{ $i->programa->cursos->map(fn ($c) => $c->numero_texto)->filter()->implode(', ') }}</div>@endif
                                        </td>
                                        <td class="text-end">@usd($i->precio)</td>
                                    </tr>
                                @endforeach
                                <tr><td colspan="2" class="text-end"><b>Total del pedido</b></td><td class="text-end"><b>@usd($pedido->total)</b></td></tr>
                            </tbody>
                        </table>
                    </div>
                    @if ($pedido->nota)
                        <div class="alert border-0 bg-light-warning mb-0"><i class="fa-solid fa-comment me-1"></i> <b>Comentario del cliente:</b> {{ $pedido->nota }}</div>
                    @endif
                </div>
            </div>

            {{-- Pago y constancia --}}
            <div class="card radius-10">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fa-solid fa-money-check-dollar me-1"></i> Pago declarado</h6>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <a href="{{ route('admin.pedidos.captura', $pedido) }}" target="_blank" class="adm-captura" title="Abrir la constancia">
                                @if ($esPdf)
                                    <div class="py-5"><i class="fa-solid fa-file-pdf text-danger" style="font-size: 54px"></i><p class="mb-0 mt-2">Abrir constancia (PDF)</p></div>
                                @else
                                    <img src="{{ route('admin.pedidos.captura', $pedido) }}" alt="Constancia del pago">
                                @endif
                            </a>
                        </div>
                        <div class="col-md-7">
                            <dl class="row mb-0 font-14">
                                <dt class="col-5 text-secondary fw-normal">Monto</dt><dd class="col-7"><b class="fs-5">{{ $simbolo }} {{ number_format((float) $pedido->pago_monto, 2) }}</b>
</dd>
                                <dt class="col-5 text-secondary fw-normal">Debía pagar</dt><dd class="col-7">{{ $simbolo }} {{ number_format($esperado, 2) }}</dd>
                                <dt class="col-5 text-secondary fw-normal">Medio</dt><dd class="col-7">{{ $pedido->metodo_texto }}</dd>
                                <dt class="col-5 text-secondary fw-normal">N.º operación</dt><dd class="col-7"><b>{{ $pedido->pago_operacion }}</b> <button type="button" class="btn btn-sm btn-link p-0 ms-1" data-copiar="{{ $pedido->pago_operacion }}" title="Copiar"><i class="fa-regular fa-copy"></i></button></dd>
                                <dt class="col-5 text-secondary fw-normal">Fecha del pago</dt><dd class="col-7">{{ $pedido->pago_fecha?->format('d/m/Y') }}</dd>
                                <dt class="col-5 text-secondary fw-normal">Registrado</dt><dd class="col-7">{{ $pedido->created_at->format('d/m/Y H:i') }} ({{ $pedido->created_at->diffForHumans() }})</dd>
                            </dl>
                        </div>
                    </div>

                    {{-- Revisión: lo que el sistema comprobó + lectura automática --}}
                    <div class="revision-pago mt-3">
                        <h6 class="mb-2"><i class="fa-solid fa-magnifying-glass me-1"></i> Revisión de la constancia</h6>
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <p class="font-13 text-secondary mb-1">Al recibirla, el sistema comprobó:</p>
                                <ul class="ocr-lista">
                                    <li><i class="fa-solid fa-circle-check text-success"></i><span>{{ $esPdf ? 'Es un PDF válido' : 'Es una imagen válida' }}@if (! empty($archivo['ancho'])) ({{ $archivo['ancho'] }} × {{ $archivo['alto'] }} px)@endif.</span></li>
                                    <li><i class="fa-solid fa-circle-check text-success"></i><span>Ni este archivo ni el n.º de operación se usaron en otro pedido.</span></li>
                                    @forelse ($alertas as $a)
                                        <li><i class="fa-solid fa-triangle-exclamation text-warning"></i><span>{{ $a }}</span></li>
                                    @empty
                                        <li><i class="fa-solid fa-circle-check text-success"></i><span>No muestra señales de edición.</span></li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="col-lg-7">
                                <p class="font-13 text-secondary mb-1">Lectura automática de la constancia:</p>
                                @if ($esPdf)
                                    <p class="font-13 mb-0"><i class="fa-solid fa-file-pdf me-1"></i> Constancia en PDF: ábrala y revise el monto, la fecha, el n.º de operación y el titular.</p>
                                @else
                                    <div class="ocr-caja" data-ocr="{{ $pedido->id }}-{{ $pedido->pago_captura_hash }}"
                                         data-src="{{ route('admin.pedidos.captura', $pedido) }}" data-base="{{ asset('vendor/tesseract') }}"
                                         data-metodo="{{ $pedido->pago_metodo }}" data-simbolo="{{ $simbolo }}" data-monto="{{ number_format((float) $pedido->pago_monto, 2, '.', '') }}"
                                         data-operacion="{{ \App\Models\Pedido::operaciones($pedido->pago_operacion)[0] ?? $pedido->pago_operacion }}" data-titulares='@json(array_values(array_filter([config('cursos.titular')])))'
                                         data-fecha-reserva="{{ $pedido->pago_fecha?->toDateString() }}" data-fecha-envio="{{ $pedido->created_at->toDateString() }}">
                                        <p class="font-13 mb-1 text-secondary"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span><span data-ocr-estado>Leyendo la constancia…</span></p>
                                        <div class="progress" style="height: 6px"><div class="progress-bar" data-ocr-avance style="width: 0%"></div></div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <p class="font-13 mb-0 mt-2 border-top pt-2"><i class="fa-solid fa-building-columns me-1"></i> <b>Antes de enviar el acceso, revise su panel de Niubiz</b> y confirme que entraron <b>{{ $simbolo }} {{ number_format((float) $pedido->pago_monto, 2) }}</b> con la operación <b>{{ $pedido->pago_operacion }}</b>. Una constancia se puede editar; el dinero en su cuenta no.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="col-xl-4">
            @if (in_array($pedido->estado, ['por_verificar', 'pagado', 'enviado']))
                <div class="card radius-10 border-top border-0 border-4 border-success">
                    <div class="card-body">
                        @if ($pedido->estado === 'por_verificar')
                            <h6><i class="fa-solid fa-circle-check text-success me-1"></i> ¿El pago llegó a su cuenta?</h6>
                            <p class="font-13 text-secondary">Puede enviar los enlaces de una vez, o solo marcar el pago como verificado y enviarlos después.</p>
                            <form method="POST" action="{{ route('admin.pedidos.verificar', $pedido) }}" class="mb-3" data-confirmar="¿Marcar el pago de {{ $pedido->codigo }} como verificado?">
                                @csrf
                                <button class="btn btn-outline-success w-100" type="submit"><i class="fa-solid fa-check me-1"></i> Solo marcar «pago verificado»</button>
                            </form>
                        @else
                            <h6><i class="fa-solid fa-paper-plane text-success me-1"></i> {{ $pedido->estado === 'enviado' ? 'Acceso enviado' : 'Enviar el acceso' }}</h6>
                            @if ($pedido->estado === 'enviado')
                                <p class="font-13 text-secondary mb-2">Enviado el {{ $pedido->enviado_en?->format('d/m/Y H:i') }}{{ $pedido->atendidoPor ? ' por ' . $pedido->atendidoPor->name : '' }}. Puede corregir los enlaces y reenviar.</p>
                            @endif
                        @endif

                        <form method="POST" action="{{ route('admin.pedidos.enviar', $pedido) }}" @if ($pedido->estado === 'por_verificar') data-confirmar="¿Verificó el pago en su cuenta? Se enviarán los enlaces a {{ $pedido->email }}." @endif>
                            @csrf
                            <label class="form-label" for="enlaces">Enlaces de descarga <small class="text-secondary">(WeTransfer, Drive… uno por línea)</small></label>
                            <textarea class="form-control mb-1 @error('enlace_descarga') is-invalid @enderror" id="enlaces" name="enlace_descarga" rows="4" required placeholder="https://we.tl/t-…">{{ old('enlace_descarga', $pedido->enlace_descarga ?: $enlacesCursos) }}</textarea>
                            @if (! $pedido->enlace_descarga && $enlacesCursos)
                                <p class="font-12 text-success mb-2"><i class="fa-solid fa-wand-magic-sparkles"></i> Enlaces tomados de los cursos. Revise que sigan vigentes (WeTransfer vence en unos días).</p>
                            @endif
                            @if ($sinEnlace->isNotEmpty() && ! $pedido->enlace_descarga)
                                <p class="font-12 text-warning mb-2"><i class="fa-solid fa-triangle-exclamation"></i> Sin enlace guardado: {{ $sinEnlace->map(fn ($c) => $c->numero ? '#' . $c->numero : $c->titulo)->implode(', ') }}.</p>
                            @endif
                            <label class="form-label" for="mensaje">Mensaje para el cliente <small class="text-secondary">(opcional)</small></label>
                            <textarea class="form-control mb-3" id="mensaje" name="mensaje_cliente" rows="3" placeholder="Ej.: el certificado le llegará en 48 horas.">{{ old('mensaje_cliente', $pedido->mensaje_cliente) }}</textarea>
                            <button class="btn btn-success w-100" type="submit"><i class="fa-solid fa-paper-plane me-1"></i> {{ $pedido->estado === 'por_verificar' ? 'Verificar y enviar el acceso' : ($pedido->estado === 'enviado' ? 'Reenviar el correo' : 'Enviar el acceso') }}</button>
                        </form>
                        @unless (\App\Support\Correo::configurado())
                            <p class="font-12 text-warning mt-2 mb-0"><i class="fa-solid fa-triangle-exclamation"></i> El correo aún no está configurado (MAIL_ en el archivo .env): los mensajes no salen. Use WhatsApp mientras tanto.</p>
                        @endunless
                        @if ($pedido->estado === 'enviado')
                            <a class="btn btn-outline-success w-100 mt-2" target="_blank" rel="noopener" href="https://wa.me/{{ $cel }}?text={{ rawurlencode($waEnlaces) }}"><i class="fa-brands fa-whatsapp me-1"></i> Enviar los enlaces por WhatsApp</a>
                        @endif
                    </div>
                </div>
            @endif

            @if (in_array($pedido->estado, ['por_verificar', 'pagado']))
                <div class="card radius-10 border-top border-0 border-4 border-danger">
                    <div class="card-body">
                        <h6><i class="fa-solid fa-ban text-danger me-1"></i> ¿El pago no llegó?</h6>
                        <form method="POST" action="{{ route('admin.pedidos.rechazar', $pedido) }}" data-confirmar="¿Rechazar el pedido {{ $pedido->codigo }}? Se avisará al cliente.">
                            @csrf
                            <label class="form-label" for="motivo">Motivo (lo verá el cliente)</label>
                            <textarea class="form-control mb-2" id="motivo" name="motivo_rechazo" rows="2" required minlength="5" maxlength="500" placeholder="Ej.: el pago no aparece en nuestra cuenta."></textarea>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                @foreach (['El pago no aparece en nuestra cuenta.', 'El monto no corresponde al precio del curso.', 'La constancia no se puede leer. Envíenos una captura completa.'] as $m)
                                    <button type="button" class="btn btn-light btn-sm font-12" onclick="document.getElementById('motivo').value = this.textContent">{{ $m }}</button>
                                @endforeach
                            </div>
                            <button class="btn btn-outline-danger w-100" type="submit">Rechazar pedido</button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($pedido->estado === 'rechazado')
                <div class="card radius-10 border-top border-0 border-4 border-danger">
                    <div class="card-body">
                        <h6 class="text-danger">Pedido rechazado</h6>
                        <p class="mb-3">{{ $pedido->motivo_rechazo }}</p>
                        <form method="POST" action="{{ route('admin.pedidos.reabrir', $pedido) }}">
                            @csrf
                            <button class="btn btn-outline-secondary w-100" type="submit"><i class="fa-solid fa-rotate-left me-1"></i> Volver a «Por verificar»</button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card radius-10">
                <div class="card-body">
                    <h6>Contactar al cliente</h6>
                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-success" target="_blank" rel="noopener" href="https://wa.me/{{ $cel }}?text={{ rawurlencode("Hola {$pedido->nombre}, le saluda InterMet sobre su pedido {$pedido->codigo}.") }}"><i class="fa-brands fa-whatsapp me-1"></i> WhatsApp {{ $pedido->celular }}</a>
                        <a class="btn btn-outline-primary" href="mailto:{{ $pedido->email }}?subject={{ rawurlencode('Su pedido ' . $pedido->codigo . ' · InterMet') }}"><i class="fa-solid fa-envelope me-1"></i> {{ $pedido->email }}</a>
                        <a class="btn btn-light" href="{{ $pedido->url_estado }}" target="_blank"><i class="fa-solid fa-eye me-1"></i> Ver lo que ve el cliente</a>
                    </div>
                    @if ($otros->isNotEmpty())
                        <hr>
                        <p class="font-13 text-secondary mb-1">Otros pedidos de este correo:</p>
                        @foreach ($otros as $o)
                            <a href="{{ route('admin.pedidos.show', $o) }}" class="d-flex justify-content-between font-13 py-1"><span>{{ $o->codigo }} · {{ $o->created_at->format('d/m/Y') }}</span><span class="badge bg-{{ $o->estado_color }}">{{ $o->estado_texto }}</span></a>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
