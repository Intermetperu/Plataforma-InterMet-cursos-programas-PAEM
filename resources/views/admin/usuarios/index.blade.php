@extends('layouts.admin')

@section('title', 'Usuarios y roles')
@section('seccion', 'Gestión')

@section('acciones')
    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalRol" data-modo="nuevo"><i class="fa-solid fa-user-shield me-1"></i> Nuevo rol</button>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario" data-modo="nuevo"><i class="fa-solid fa-user-plus me-1"></i> Nuevo usuario</button>
@endsection

@section('content')
    @php
        $pestanas = ['usuarios' => ['fa-solid fa-users', 'Personal'], 'clientes' => ['fa-solid fa-address-book', 'Clientes (' . $clientes->count() . ')'], 'roles' => ['fa-solid fa-user-shield', 'Roles y permisos']];
        if (! isset($pestanas[$tab])) { $tab = 'usuarios'; }
    @endphp

    <div class="card radius-10">
        <div class="card-body">
            <ul class="nav nav-tabs nav-primary" role="tablist">
                @foreach ($pestanas as $clave => [$icono, $texto])
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $tab === $clave ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-{{ $clave }}" role="tab" aria-selected="{{ $tab === $clave ? 'true' : 'false' }}">
                            <div class="d-flex align-items-center"><div class="tab-icon"><i class="{{ $icono }} me-1"></i></div><div class="tab-title">{{ $texto }}</div></div>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content py-4">
                {{-- ================= TRABAJADORES ================= --}}
                <div class="tab-pane fade {{ $tab === 'usuarios' ? 'show active' : '' }}" id="tab-usuarios" role="tabpanel">
                    <p class="text-secondary">Cada usuario entra al panel con su correo y contraseña. Su <b>rol</b> define qué puede hacer.</p>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light"><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
                            <tbody>
                                @foreach ($usuarios as $u)
                                    <tr class="{{ $u->activo ? '' : 'text-secondary' }}">
                                        <td><b>{{ $u->name }}</b> @if ($u->is(auth()->user())) <span class="badge bg-light-primary text-primary">Tú</span> @endif</td>
                                        <td>{{ $u->email }}</td>
                                        <td><span class="badge {{ $u->role?->acceso_total ? 'bg-primary' : 'bg-info text-dark' }}">{{ $u->nombre_rol }}</span></td>
                                        <td>@if ($u->activo)<span class="badge bg-success">Activo</span>@else<span class="badge bg-secondary">Desactivado</span>@endif</td>
                                        <td class="text-center text-nowrap">
                                            <button type="button" class="btn btn-inverse-primary btn-sm" title="Editar" data-bs-toggle="modal" data-bs-target="#modalUsuario"
                                                    data-modo="editar" data-url="{{ route('admin.usuarios.update', $u) }}" data-name="{{ $u->name }}" data-email="{{ $u->email }}"
                                                    data-role_id="{{ $u->role_id }}" data-activo="{{ $u->activo ? 1 : 0 }}" data-yo="{{ $u->is(auth()->user()) ? 1 : 0 }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @unless ($u->is(auth()->user()))
                                                <form method="POST" action="{{ route('admin.usuarios.destroy', $u) }}" class="d-inline" data-confirmar="¿Eliminar a {{ $u->name }}? Si ya atendió pedidos solo se desactivará.">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-inverse-danger btn-sm" title="Eliminar"><i class="fas fa-trash"></i></button>
                                                </form>
                                            @endunless
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ================= CLIENTES (se registran en la web) ================= --}}
                <div class="tab-pane fade {{ $tab === 'clientes' ? 'show active' : '' }}" id="tab-clientes" role="tabpanel">
                    <p class="text-secondary">Personas que crearon su cuenta en la web con «Registrarse». Solo pueden ver la web y sus pedidos: no entran al panel.</p>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light"><tr><th>Cliente</th><th>Celular</th><th>País</th><th class="text-center">Pedidos</th><th>Registrado</th></tr></thead>
                            <tbody>
                                @forelse ($clientes as $c)
                                    <tr>
                                        <td><b>{{ $c->name }}</b><br><small class="text-secondary">{{ $c->email }}</small></td>
                                        <td>@if ($c->celular)<a href="https://wa.me/{{ preg_replace('/\D/', '', $c->celular) }}" target="_blank" rel="noopener"><i class="bx bxl-whatsapp text-success"></i> {{ $c->celular }}</a>@endif</td>
                                        <td>{{ $c->pais }}</td>
                                        <td class="text-center">@if ($c->pedidos_count)<a href="{{ route('admin.pedidos.index', ['estado' => '', 'q' => $c->email]) }}">{{ $c->pedidos_count }}</a>@else 0 @endif</td>
                                        <td class="text-nowrap">{{ $c->created_at->format('d/m/Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-secondary">Todavía no se registra ningún cliente.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ================= ROLES ================= --}}
                <div class="tab-pane fade {{ $tab === 'roles' ? 'show active' : '' }}" id="tab-roles" role="tabpanel">
                    <p class="text-secondary">Un rol es un grupo de permisos. Crea los que necesites (por ejemplo «Ventas» o «Contenido») y asígnalos a los usuarios.</p>
                    <div class="row">
                        @foreach ($roles as $r)
                            <div class="col-md-6 col-xl-4 mb-3">
                                <div class="card border h-100 shadow-none">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start gap-2">
                                            <div>
                                                <h6 class="mb-0">{{ $r->nombre }}</h6>
                                                <small class="text-secondary">{{ $r->usuarios_count }} {{ $r->usuarios_count === 1 ? 'usuario' : 'usuarios' }}</small>
                                            </div>
                                            <div class="ms-auto text-nowrap">
                                                <button type="button" class="btn btn-inverse-primary btn-sm" title="Editar" data-bs-toggle="modal" data-bs-target="#modalRol" data-modo="editar"
                                                        data-url="{{ route('admin.roles.update', $r) }}" data-nombre="{{ $r->nombre }}" data-descripcion="{{ $r->descripcion }}"
                                                        data-permisos="{{ implode(',', $r->permisos ?? []) }}" data-total="{{ $r->acceso_total ? 1 : 0 }}"><i class="fas fa-edit"></i></button>
                                                @unless ($r->acceso_total)
                                                    <form method="POST" action="{{ route('admin.roles.destroy', $r) }}" class="d-inline" data-confirmar="¿Eliminar el rol {{ $r->nombre }}?">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-inverse-danger btn-sm" title="Eliminar"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                @endunless
                                            </div>
                                        </div>
                                        <p class="font-13 mt-2 mb-2">{{ $r->descripcion }}</p>
                                        @if ($r->acceso_total)
                                            <span class="badge bg-primary">Acceso total</span>
                                        @else
                                            @forelse ($r->permisos ?? [] as $p)
                                                <span class="badge bg-light-success text-success mb-1">{{ $permisos[$p][0] ?? $p }}</span>
                                            @empty
                                                <span class="text-danger font-13">Sin permisos</span>
                                            @endforelse
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal usuario --}}
    <div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="tituloUsuario" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content" data-url-nuevo="{{ route('admin.usuarios.store') }}" autocomplete="off">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloUsuario">Nuevo usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label" for="u-name">Nombre completo</label>
                        <input class="form-control" id="u-name" name="name" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="u-email">Correo (para iniciar sesión)</label>
                        <input type="email" class="form-control" id="u-email" name="email" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="u-rol">Rol</label>
                        <select class="form-select" id="u-rol" name="role_id" required>
                            @foreach ($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="u-password">Contraseña</label>
                        <input type="password" class="form-control" id="u-password" name="password" minlength="8">
                        <small class="text-secondary" id="u-ayuda">Mínimo 8 caracteres.</small>
                    </div>
                    <div class="col-12" id="u-activo-box">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="u-activo" name="activo" value="1" checked>
                            <label class="form-check-label" for="u-activo">Activo (puede iniciar sesión)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal rol --}}
    <div class="modal fade" id="modalRol" tabindex="-1" aria-labelledby="tituloRol" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" class="modal-content" data-url-nuevo="{{ route('admin.roles.store') }}" autocomplete="off">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloRol">Nuevo rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-5">
                        <label class="form-label" for="r-nombre">Nombre del rol</label>
                        <input class="form-control" id="r-nombre" name="nombre" required maxlength="60" placeholder="Ej.: Cajero">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label" for="r-desc">Descripción</label>
                        <input class="form-control" id="r-desc" name="descripcion" maxlength="200">
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Permisos</label>
                        <p class="text-primary font-13 d-none" id="r-total">Este rol tiene acceso total: siempre puede todo.</p>
                        <div class="row g-2" id="r-permisos">
                            @foreach ($permisos as $clave => [$nombre, $detalle])
                                <div class="col-md-6">
                                    <label class="d-flex gap-2 border rounded p-2 h-100" for="p-{{ $clave }}" style="cursor:pointer">
                                        <input class="form-check-input mt-1" type="checkbox" name="permisos[]" value="{{ $clave }}" id="p-{{ $clave }}">
                                        <span><b>{{ $nombre }}</b><br><small class="text-secondary">{{ $detalle }}</small></span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar rol</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function prepararModal(id, titulos, llenar) {
            const modal = document.getElementById(id);
            modal.addEventListener('show.bs.modal', (e) => {
                const btn = e.relatedTarget;
                const form = modal.querySelector('form');
                const editar = btn && btn.dataset.modo === 'editar';
                form.reset();
                form.action = editar ? btn.dataset.url : form.dataset.urlNuevo;
                form.querySelector('[name=_method]').value = editar ? 'PUT' : 'POST';
                modal.querySelector('.modal-title').textContent = editar ? titulos[1] : titulos[0];
                llenar(form, editar ? btn.dataset : null);
            });
        }
        prepararModal('modalUsuario', ['Nuevo usuario', 'Editar usuario'], (form, d) => {
            const campo = (n) => form.elements.namedItem(n);
            if (d) {
                campo('name').value = d.name; campo('email').value = d.email; campo('role_id').value = d.role_id;
                campo('activo').checked = d.activo === '1';
            } else {
                campo('activo').checked = true;
            }
            campo('password').required = !d;
            document.getElementById('u-ayuda').textContent = d ? 'Déjala vacía para no cambiarla.' : 'Mínimo 8 caracteres.';
            document.getElementById('u-activo-box').classList.toggle('d-none', !d || d.yo === '1');
        });
        prepararModal('modalRol', ['Nuevo rol', 'Editar rol'], (form, d) => {
            const permisos = d ? (d.permisos || '').split(',') : [];
            const total = d && d.total === '1';
            form.elements.namedItem('nombre').value = d ? d.nombre : '';
            form.elements.namedItem('descripcion').value = d ? d.descripcion : '';
            form.querySelectorAll('[name="permisos[]"]').forEach((c) => { c.checked = total || permisos.includes(c.value); c.disabled = total; });
            document.getElementById('r-total').classList.toggle('d-none', !total);
        });
    </script>
@endpush
