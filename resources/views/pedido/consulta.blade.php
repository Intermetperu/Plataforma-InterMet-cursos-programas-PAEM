@extends('layouts.public')

@section('title', 'Mi pedido')

@section('content')
    <section class="im-cabecera-simple">
        <div class="container">
            <nav aria-label="Ruta" class="im-migas"><a href="{{ route('home') }}">Cursos digitales</a><i class="bx bx-chevron-right"></i><span>Mi pedido</span></nav>
            <h1>Consultar mi pedido</h1>
            <p>Escriba el código que le enviamos por correo al registrar su pago (por ejemplo, C-000123).</p>
        </div>
    </section>
    <section class="im-sobre">
        <div class="container" style="max-width: 560px">
            <form method="POST" action="{{ route('pedido.buscar') }}" class="im-form-bloque">
                @csrf
                @if ($errors->any())
                    <div class="im-alerta error" role="alert"><i class="bx bx-error-circle"></i><div>{{ $errors->first() }}</div></div>
                @endif
                <div class="mb-3">
                    <label class="form-label" for="c-codigo">Código del pedido</label>
                    <input class="form-control" id="c-codigo" name="codigo" value="{{ old('codigo') }}" required maxlength="20" placeholder="C-000123" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="c-email">Correo con el que registró el pago</label>
                    <input type="email" class="form-control" id="c-email" name="email" value="{{ old('email') }}" required autocomplete="email">
                </div>
                <button type="submit" class="im-btn im-btn-primario w-100"><i class="bx bx-search-alt"></i> Ver mi pedido</button>
                <p class="small text-muted mt-3 mb-0">¿Aún no registra su pago? <a href="{{ route('pedido.create') }}">Regístrelo aquí</a>.</p>
            </form>
        </div>
    </section>
@endsection
