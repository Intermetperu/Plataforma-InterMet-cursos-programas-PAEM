@extends('layouts.admin')

@section('title', 'Configuración')
@section('seccion', 'Gestión')

@php
    $puede = auth()->user()->puede('configuracion');
    $pestanas = $puede
        ? ['empresa' => ['fa-building', 'Empresa y contacto'], 'pagos' => ['fa-credit-card', 'Pagos'], 'textos' => ['fa-align-left', 'Textos de la web'], 'chatbot' => ['fa-robot', 'Asistente (chat)'], 'clave' => ['fa-key', 'Mi contraseña']]
        : ['clave' => ['fa-key', 'Mi contraseña']];
    if (! isset($pestanas[$tab])) { $tab = array_key_first($pestanas); }
    $v = fn ($c) => old($c, config("cursos.$c"));
@endphp

@section('content')
    <div class="card radius-10">
        <div class="card-body">
            <ul class="nav nav-tabs nav-primary" role="tablist">
                @foreach ($pestanas as $k => [$icono, $texto])
                    <li class="nav-item" role="presentation"><a class="nav-link {{ $tab === $k ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-{{ $k }}" role="tab"><i class="fa-solid {{ $icono }} me-1"></i> {{ $texto }}</a></li>
                @endforeach
            </ul>
            <div class="tab-content py-4">
                @if ($puede)
                    <div class="tab-pane fade {{ $tab === 'empresa' ? 'show active' : '' }}" id="tab-empresa" role="tabpanel">
                        <form method="POST" action="{{ route('admin.configuracion.guardar') }}" class="row g-3">
                            @csrf <input type="hidden" name="tab" value="empresa">
                            <div class="col-md-6"><label class="form-label" for="g-nombre">Nombre</label><input class="form-control" id="g-nombre" name="nombre" value="{{ $v('nombre') }}" required></div>
                            <div class="col-md-6"><label class="form-label" for="g-eslogan">Eslogan</label><input class="form-control" id="g-eslogan" name="eslogan" value="{{ $v('eslogan') }}"></div>
                            <div class="col-md-6"><label class="form-label" for="g-correo">Correo de pedidos <small class="text-secondary">(recibe el aviso de cada pago)</small></label><input type="email" class="form-control" id="g-correo" name="correo_pedidos" value="{{ $v('correo_pedidos') }}" required></div>
                            <div class="col-md-6"><label class="form-label" for="g-inf">Correo de informes</label><input type="email" class="form-control" id="g-inf" name="correo_informes" value="{{ $v('correo_informes') }}"></div>
                            <div class="col-md-4"><label class="form-label" for="g-wa">WhatsApp <small class="text-secondary">(con código de país)</small></label><input class="form-control" id="g-wa" name="whatsapp" value="{{ $v('whatsapp') }}" required placeholder="51960995971"></div>
                            <div class="col-md-4"><label class="form-label" for="g-tel">Teléfono que se muestra</label><input class="form-control" id="g-tel" name="telefono" value="{{ $v('telefono') }}"></div>
                            <div class="col-md-4"><label class="form-label" for="g-hor">Horario de atención</label><input class="form-control" id="g-hor" name="horario" value="{{ $v('horario') }}"></div>
                            <div class="col-md-6"><label class="form-label" for="g-web">Página web principal</label><input type="url" class="form-control" id="g-web" name="web" value="{{ $v('web') }}"></div>
                            <div class="col-md-6"><label class="form-label" for="g-fb">Facebook</label><input type="url" class="form-control" id="g-fb" name="facebook" value="{{ $v('facebook') }}" placeholder="https://facebook.com/…"></div>
                            <div class="col-md-6"><label class="form-label" for="g-li">LinkedIn</label><input type="url" class="form-control" id="g-li" name="linkedin" value="{{ $v('linkedin') }}"></div>
                            <div class="col-md-6"><label class="form-label" for="g-yt">YouTube</label><input type="url" class="form-control" id="g-yt" name="youtube" value="{{ $v('youtube') }}"></div>
                            <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button></div>
                        </form>
                    </div>

                    <div class="tab-pane fade {{ $tab === 'pagos' ? 'show active' : '' }}" id="tab-pagos" role="tabpanel">
                        <form method="POST" action="{{ route('admin.configuracion.guardar') }}" class="row g-3">
                            @csrf <input type="hidden" name="tab" value="pagos">
                            <div class="col-md-6"><label class="form-label" for="g-tit">Nombre del comercio en el voucher de Niubiz</label><input class="form-control" id="g-tit" name="titular" value="{{ $v('titular') }}" required>
                                <small class="text-secondary">Se usa para comprobar en la constancia que el pago fue a su comercio.</small></div>
                            <div class="col-12"><p class="text-secondary font-13 mb-0"><i class="fa-solid fa-circle-info me-1"></i> Los pagos se hacen solo con el link de pago (Niubiz) de cada curso: <a href="{{ route('admin.cursos.index', ['filtro' => 'sin_link']) }}">ver cursos sin link</a> o <a href="{{ route('admin.cursos.importar') }}">importarlos desde Excel</a>.</p></div>
                            <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button></div>
                        </form>
                    </div>

                    <div class="tab-pane fade {{ $tab === 'textos' ? 'show active' : '' }}" id="tab-textos" role="tabpanel">
                        <form method="POST" action="{{ route('admin.configuracion.guardar') }}" class="row g-3">
                            @csrf <input type="hidden" name="tab" value="textos">
                            <div class="col-12"><label class="form-label" for="g-popup">Ventana de «Programas de alta especialización» al entrar a la portada</label>
                                <select class="form-select" id="g-popup" name="popup_programas">
                                    <option value="1" @selected($v('popup_programas') == '1')>Sí, mostrarla (al entrar a la web y cada vez que se recarga la portada)</option>
                                    <option value="0" @selected($v('popup_programas') == '0')>No mostrarla</option>
                                </select></div>
                            <div class="col-12"><label class="form-label" for="g-desc">Descripción (pie de página)</label><textarea class="form-control" id="g-desc" name="descripcion" rows="3">{{ $v('descripcion') }}</textarea></div>
                            <div class="col-md-6"><label class="form-label" for="g-inc">Todos los cursos incluyen <small class="text-secondary">(uno por línea)</small></label><textarea class="form-control" id="g-inc" name="incluye" rows="5">{{ $v('incluye') }}</textarea></div>
                            <div class="col-md-6"><label class="form-label" for="g-dir">«Dirigido a» general <small class="text-secondary">(para cursos que no tienen el suyo)</small></label><textarea class="form-control" id="g-dir" name="dirigido_a" rows="5">{{ $v('dirigido_a') }}</textarea></div>
                            <div class="col-md-8"><label class="form-label" for="g-env">Mensaje del paso 3 (envío)</label><input class="form-control" id="g-env" name="envio" value="{{ $v('envio') }}"></div>
                            <div class="col-md-4"><label class="form-label" for="g-pp">Cursos por página en el catálogo</label><input type="number" class="form-control" id="g-pp" name="por_pagina" value="{{ $v('por_pagina') }}" min="6" max="48" required></div>
                            <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button></div>
                        </form>
                    </div>

                    <div class="tab-pane fade {{ $tab === 'chatbot' ? 'show active' : '' }}" id="tab-chatbot" role="tabpanel">
                        <form method="POST" action="{{ route('admin.configuracion.guardar') }}" class="row g-3">
                            @csrf <input type="hidden" name="tab" value="chatbot">
                            <div class="col-12"><p class="text-secondary font-13 mb-0"><i class="fa-solid fa-circle-info me-1"></i> El asistente aparece en la parte lateral derecha de la web. Responde solo con los datos de la plataforma: busca cursos por tema, instructor, N°, año o idioma, da los precios y explica la compra, el envío, el certificado y el contacto. Si no sabe algo, ofrece WhatsApp.</p></div>
                            <div class="col-md-4"><label class="form-label" for="g-bot-act">Mostrar el asistente en la web</label>
                                <select class="form-select" id="g-bot-act" name="chatbot_activo">
                                    <option value="1" @selected($v('chatbot_activo') == '1')>Sí, mostrarlo</option>
                                    <option value="0" @selected($v('chatbot_activo') == '0')>No, ocultarlo</option>
                                </select></div>
                            <div class="col-md-8"><label class="form-label" for="g-bot-nom">Nombre del asistente</label><input class="form-control" id="g-bot-nom" name="chatbot_nombre" value="{{ $v('chatbot_nombre') }}" maxlength="40" required></div>
                            <div class="col-md-8"><label class="form-label" for="g-bot-auto">Al entrar a la web</label>
                                <select class="form-select" id="g-bot-auto" name="chatbot_auto">
                                    <option value="panel" @selected($v('chatbot_auto') === 'panel')>Aparecer solo: se abre en la PC y saluda junto al botón en el celular</option>
                                    <option value="saludo" @selected($v('chatbot_auto') === 'saludo')>Solo mostrar el saludo junto al botón (PC y celular)</option>
                                    <option value="no" @selected($v('chatbot_auto') === 'no')>No aparecer solo: únicamente cuando hagan clic</option>
                                </select>
                                <small class="text-secondary">Sale una vez por visita, con animación. En iniciar sesión, registrarse y registrar pago no interrumpe.</small></div>
                            <div class="col-md-4"><label class="form-label" for="g-bot-seg">Aparece a los (segundos)</label><input type="number" class="form-control" id="g-bot-seg" name="chatbot_auto_seg" value="{{ $v('chatbot_auto_seg') }}" min="0" max="30" required></div>
                            <div class="col-12"><label class="form-label" for="g-bot-bien">Mensaje de bienvenida</label><textarea class="form-control" id="g-bot-bien" name="chatbot_bienvenida" rows="3" maxlength="400" required>{{ $v('chatbot_bienvenida') }}</textarea></div>
                            <div class="col-md-4"><label class="form-label" for="g-bot-ia">Respuestas con IA <small class="text-secondary">(opcional)</small></label>
                                <select class="form-select" id="g-bot-ia" name="chatbot_ia">
                                    <option value="0" @selected($v('chatbot_ia') != '1')>No, solo respuestas automáticas</option>
                                    <option value="1" @selected($v('chatbot_ia') == '1')>Sí, redactar con IA (Claude)</option>
                                </select></div>
                            <div class="col-md-8 d-flex align-items-end">
                                @if (config('services.anthropic.key'))
                                    <p class="font-13 mb-2 text-success"><i class="fa-solid fa-circle-check me-1"></i> La clave de la IA está configurada. Si la IA falla, el asistente responde igual con las respuestas automáticas.</p>
                                @else
                                    <p class="font-13 mb-2 text-secondary"><i class="fa-solid fa-key me-1"></i> Para usar IA, agregue <code>ANTHROPIC_API_KEY=…</code> en el archivo <code>.env</code> (se paga por uso en console.anthropic.com). Sin la clave, el asistente funciona gratis con respuestas automáticas.</p>
                                @endif
                            </div>
                            <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button></div>
                        </form>
                    </div>
                @endif

                <div class="tab-pane fade {{ $tab === 'clave' ? 'show active' : '' }}" id="tab-clave" role="tabpanel">
                    <form method="POST" action="{{ route('admin.configuracion.clave') }}" class="row g-3" style="max-width: 520px" autocomplete="off">
                        @csrf
                        <div class="col-12"><label class="form-label" for="k-act">Contraseña actual</label><input type="password" class="form-control" id="k-act" name="actual" required autocomplete="current-password"></div>
                        <div class="col-12"><label class="form-label" for="k-new">Contraseña nueva</label><input type="password" class="form-control" id="k-new" name="password" required minlength="8" autocomplete="new-password"></div>
                        <div class="col-12"><label class="form-label" for="k-conf">Repita la contraseña nueva</label><input type="password" class="form-control" id="k-conf" name="password_confirmation" required minlength="8" autocomplete="new-password"></div>
                        <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-key me-1"></i> Cambiar contraseña</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
