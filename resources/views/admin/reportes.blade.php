@extends('layouts.admin')

@section('title', 'Reportes de ventas')
@section('seccion', 'Ventas')

@section('content')
    <div class="card radius-10">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto"><label class="form-label" for="r-desde">Desde</label><input type="date" class="form-control" id="r-desde" name="desde" value="{{ $desde->toDateString() }}"></div>
                <div class="col-auto"><label class="form-label" for="r-hasta">Hasta</label><input type="date" class="form-control" id="r-hasta" name="hasta" value="{{ $hasta->toDateString() }}"></div>
                <div class="col-auto"><button class="btn btn-primary" type="submit">Ver</button></div>
                <div class="col-auto ms-auto"><a class="btn btn-outline-success" href="{{ request()->fullUrlWithQuery(['exportar' => 1]) }}"><i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel</a></div>
            </form>
            <p class="text-secondary font-13 mt-2 mb-0">Solo pedidos con el pago verificado, según la fecha en que se verificó. Montos en USD según el precio de los cursos.</p>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2">
        <div class="col"><div class="card radius-10"><div class="card-body"><p class="mb-0 text-secondary">Ventas del periodo</p><h3 class="mb-0 text-success">@usd($total)</h3></div></div></div>
        <div class="col"><div class="card radius-10"><div class="card-body"><p class="mb-0 text-secondary">Pedidos verificados</p><h3 class="mb-0">{{ $cantidad }}</h3></div></div></div>
    </div>

    <div class="row">
        <div class="col-xl-4">
            <div class="card radius-10"><div class="card-body">
                <h6>Por mes</h6>
                <table class="table table-sm mb-0">
                    @forelse ($porMes as $mes => $d)
                        <tr><td>{{ ucfirst(\Illuminate\Support\Carbon::createFromFormat('Y-m', $mes)->translatedFormat('F Y')) }}</td><td class="text-end">{{ $d['pedidos'] }}</td><td class="text-end"><b>@usd($d['total'])</b></td></tr>
                    @empty
                        <tr><td class="text-secondary">Sin ventas en el periodo.</td></tr>
                    @endforelse
                </table>
            </div></div>
        </div>
        <div class="col-xl-8">
            <div class="card radius-10"><div class="card-body">
                <h6>Por curso</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>N°</th><th>Curso</th><th class="text-end">Vendidos</th><th class="text-end">Total</th></tr></thead>
                        @forelse ($porCurso as $c)
                            <tr><td>{{ $c->numero ? '#' . $c->numero : '—' }}</td><td>{{ $c->titulo }}</td><td class="text-end">{{ $c->veces }}</td><td class="text-end"><b>@usd($c->total)</b></td></tr>
                        @empty
                            <tr><td colspan="4" class="text-secondary">Sin ventas en el periodo.</td></tr>
                        @endforelse
                    </table>
                </div>
            </div></div>
        </div>
    </div>
@endsection
