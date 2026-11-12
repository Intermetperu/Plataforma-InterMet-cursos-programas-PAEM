@extends('layouts.admin')

@section('title', 'Pedidos')
@section('seccion', 'Ventas')

@section('content')
    <div class="card radius-10">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                <ul class="nav nav-pills gap-1" role="tablist">
                    <li class="nav-item"><a class="nav-link {{ ! $estado ? 'active' : '' }}" href="{{ route('admin.pedidos.index', ['estado' => '']) }}">Todos <span class="badge bg-secondary ms-1">{{ $cuentas->sum() }}</span></a></li>
                    @foreach (\App\Models\Pedido::ESTADOS as $k => [$texto, $color])
                        <li class="nav-item"><a class="nav-link {{ $estado === $k ? 'active' : '' }}" href="{{ route('admin.pedidos.index', ['estado' => $k]) }}">{{ $texto }} <span class="badge bg-{{ $color }} ms-1">{{ $cuentas[$k] ?? 0 }}</span></a></li>
                    @endforeach
                </ul>
                <form method="GET" class="d-flex gap-2" role="search">
                    @if ($estado)<input type="hidden" name="estado" value="{{ $estado }}">@endif
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Código, cliente, correo u operación" aria-label="Buscar pedido">
                    <button class="btn btn-outline-primary" type="submit" aria-label="Buscar"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>

            @if ($estado === 'por_verificar' && $pedidos->total())
                <p class="text-secondary font-13"><i class="fa-solid fa-circle-info me-1"></i> Los más antiguos primero. Abra cada pedido, revise la constancia en su cuenta y envíe el acceso.</p>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>Pedido</th><th>Cliente</th><th>Cursos</th><th class="text-end">Pagó</th><th>Medio</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($pedidos as $p)
                            <tr>
                                <td class="text-nowrap"><a href="{{ route('admin.pedidos.show', $p) }}"><b>{{ $p->codigo }}</b></a><br><small class="text-secondary">{{ $p->created_at->format('d/m/Y H:i') }}</small></td>
                                <td><b>{{ $p->nombre }}</b><br><small class="text-secondary">{{ $p->email }} · {{ $p->pais }}</small></td>
                                <td class="font-13" style="max-width: 320px">
                                    @foreach ($p->items->take(2) as $i)<div class="text-truncate">{{ $i->numero ? '#' . $i->numero : '' }} {{ $i->titulo }}</div>@endforeach
                                    @if ($p->items->count() > 2)<small class="text-secondary">y {{ $p->items->count() - 2 }} más</small>@endif
                                </td>
                                <td class="text-end text-nowrap"><b>{{ $p->pago_moneda === 'PEN' ? 'S/' : 'USD' }} {{ number_format((float) $p->pago_monto, 2) }}</b><br><small class="text-secondary">total @usd($p->total)</small></td>
                                <td class="font-13">{{ \Illuminate\Support\Str::before($p->metodo_texto, ' (') }}<br><small class="text-secondary">op. {{ $p->pago_operacion }}</small></td>
                                <td>
                                    <span class="badge bg-{{ $p->estado_color }}">{{ $p->estado_texto }}</span>
                                    @if (! empty($p->pago_alertas['avisos']))<br><small class="text-warning"><i class="fa-solid fa-triangle-exclamation"></i> {{ count($p->pago_alertas['avisos']) }} aviso{{ count($p->pago_alertas['avisos']) > 1 ? 's' : '' }}</small>@endif
                                </td>
                                <td class="text-end"><a href="{{ route('admin.pedidos.show', $p) }}" class="btn btn-sm {{ $p->estado === 'por_verificar' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $p->estado === 'por_verificar' ? 'Revisar' : 'Ver' }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-secondary py-4">{{ $q !== '' ? 'No hay pedidos con «' . $q . '».' : 'No hay pedidos aquí.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $pedidos->links() }}
        </div>
    </div>
@endsection
