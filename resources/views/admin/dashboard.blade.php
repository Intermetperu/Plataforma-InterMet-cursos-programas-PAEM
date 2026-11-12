@extends('layouts.admin')

@section('title', 'Tablero')
@section('seccion', 'Ventas')

@section('content')
    @php $max = max(1, $meses->max('total')); @endphp

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
        <div class="col">
            <a href="{{ route('admin.pedidos.index') }}" class="card radius-10 border-start border-0 border-4 border-warning text-decoration-none">
                <div class="card-body d-flex align-items-center">
                    <div><p class="mb-0 text-secondary">Pagos por verificar</p><h4 class="my-1 text-warning">{{ $porVerificar }}</h4><p class="mb-0 font-13">{{ $porEnviar }} pagados, falta enviar el acceso</p></div>
                    <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning ms-auto"><i class="fa-solid fa-hourglass-half"></i></div>
                </div>
            </a>
        </div>
        <div class="col">
            <div class="card radius-10 border-start border-0 border-4 border-success">
                <div class="card-body d-flex align-items-center">
                    <div><p class="mb-0 text-secondary">Ventas de {{ now()->translatedFormat('F') }}</p><h4 class="my-1 text-success">@usd($ventasMes)</h4><p class="mb-0 font-13">{{ $pedidosMes }} {{ $pedidosMes === 1 ? 'pedido' : 'pedidos' }}</p></div>
                    <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto"><i class="fa-solid fa-dollar-sign"></i></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card radius-10 border-start border-0 border-4 border-primary">
                <div class="card-body d-flex align-items-center">
                    <div><p class="mb-0 text-secondary">Vendido en total</p><h4 class="my-1 text-primary">@usd($ventasTotal)</h4><p class="mb-0 font-13">pedidos verificados</p></div>
                    <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto"><i class="fa-solid fa-chart-line"></i></div>
                </div>
            </div>
        </div>
        <div class="col">
            <a href="{{ route('admin.cursos.index') }}" class="card radius-10 border-start border-0 border-4 border-info text-decoration-none">
                <div class="card-body d-flex align-items-center">
                    <div><p class="mb-0 text-secondary">Cursos publicados</p><h4 class="my-1 text-info">{{ $cursosPublicados }}</h4><p class="mb-0 font-13">{{ $cursosSinLink }} sin link de pago</p></div>
                    <div class="widgets-icons-2 rounded-circle bg-light-info text-info ms-auto"><i class="fa-solid fa-graduation-cap"></i></div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7">
            <div class="card radius-10">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2"><h6 class="mb-0">Pagos por verificar</h6>
                        <a href="{{ route('admin.pedidos.index') }}" class="ms-auto font-13">Ver todos</a></div>
                    @forelse ($porVerificarLista as $p)
                        <a href="{{ route('admin.pedidos.show', $p) }}" class="d-flex align-items-center gap-3 py-2 border-bottom text-decoration-none text-dark">
                            <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning" style="width:42px;height:42px;font-size:18px"><i class="fa-solid fa-receipt"></i></div>
                            <div class="flex-grow-1 min-w-0">
                                <b>{{ $p->codigo }}</b> · {{ $p->nombre }}<br>
                                <small class="text-secondary">{{ $p->items->count() }} {{ $p->items->count() === 1 ? 'curso' : 'cursos' }} · {{ $p->metodo_texto }} · {{ $p->created_at->diffForHumans() }}</small>
                            </div>
                            <b class="text-nowrap">{{ $p->pago_moneda === 'PEN' ? 'S/' : 'USD' }} {{ number_format((float) $p->pago_monto, 2) }}</b>
                        </a>
                    @empty
                        <p class="text-secondary mb-0 py-3"><i class="fa-solid fa-circle-check text-success me-1"></i> No hay pagos pendientes de revisar.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card radius-10">
                <div class="card-body">
                    <h6 class="mb-0">Ventas de los últimos 6 meses (USD)</h6>
                    <div class="adm-barras" role="img" aria-label="Ventas por mes">
                        @foreach ($meses as $m)
                            <div class="adm-barra"><b>{{ $m['total'] ? number_format($m['total']) : '' }}</b><div style="height: {{ max(2, round($m['total'] * 100 / $max)) }}%"></div><span>{{ ucfirst($m['mes']) }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7">
            <div class="card radius-10">
                <div class="card-body">
                    <h6 class="mb-3">Últimos pedidos</h6>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light"><tr><th>Pedido</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
                            <tbody>
                                @forelse ($ultimos as $p)
                                    <tr>
                                        <td><a href="{{ route('admin.pedidos.show', $p) }}"><b>{{ $p->codigo }}</b></a><br><small class="text-secondary">{{ $p->created_at->format('d/m H:i') }}</small></td>
                                        <td>{{ $p->nombre }}<br><small class="text-secondary">{{ $p->pais }}</small></td>
                                        <td>@usd($p->total)</td>
                                        <td><span class="badge bg-{{ $p->estado_color }}">{{ $p->estado_texto }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-secondary">Todavía no hay pedidos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card radius-10">
                <div class="card-body">
                    <h6 class="mb-3">Cursos más vendidos</h6>
                    @forelse ($masVendidos as $v)
                        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                            <span class="badge bg-light-primary text-primary">{{ $v->numero ? '#' . $v->numero : '—' }}</span>
                            <span class="flex-grow-1 font-13">{{ \Illuminate\Support\Str::limit($v->titulo, 60) }}</span>
                            <b class="text-nowrap">{{ $v->veces }} ×</b>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">Aún no hay ventas verificadas.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
