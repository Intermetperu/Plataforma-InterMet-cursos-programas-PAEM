@extends('layouts.public')

@section('title', 'Registrarse')

@php
    $paises = ['Perú', 'Chile', 'México', 'Colombia', 'Ecuador', 'Bolivia', 'Argentina', 'Brasil', 'Venezuela', 'Panamá', 'Guatemala', 'Honduras', 'Paraguay', 'Uruguay', 'República Dominicana', 'España', 'Estados Unidos', 'Canadá', 'Australia', 'Sudáfrica'];
@endphp

@section('content')
    <section class="im-cabecera-simple">
        <div class="container">
            <nav aria-label="Ruta" class="im-migas"><a href="{{ route('home') }}">Cursos digitales</a><i class="bx bx-chevron-right"></i><span>Registrarse</span></nav>
            <h1>Cree su cuenta</h1>
            <p>Es gratis. Con su cuenta sus datos se llenan solos al registrar un pago y ve todos sus pedidos en un solo lugar.</p>
        </div>
    </section>

    <section class="im-sobre">
        <div class="container im-cuenta-caja">
            <form method="POST" action="{{ route('registro.store') }}" class="im-form-bloque im-cuenta" novalidate>
                @csrf
                <div class="im-cuenta-tabs" role="tablist">
                    <a href="{{ route('login') }}">Iniciar sesión</a>
                    <a href="{{ route('registro') }}" class="activo" aria-current="page">Registrarse</a>
                </div>

                @if ($errors->any())
                    <div class="im-alerta error" role="alert"><i class="bx bx-error-circle"></i><div><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>
                @endif

                <div class="mb-3">
                    <label class="form-label" for="r-nombre">Nombres y apellidos</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="r-nombre" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" autofocus>
                    <small class="text-muted">Así saldrá en su certificado.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="r-email">Correo electrónico</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="r-email" name="email" value="{{ old('email') }}" required maxlength="150" autocomplete="email">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="r-celular">Celular / WhatsApp</label>
                        <input class="form-control @error('celular') is-invalid @enderror" id="r-celular" name="celular" value="{{ old('celular') }}" required maxlength="30" autocomplete="tel" placeholder="+51 999 999 999">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="r-pais">País</label>
                        <input class="form-control" id="r-pais" name="pais" value="{{ old('pais', 'Perú') }}" required maxlength="60" list="r-paises" autocomplete="country-name">
                        <datalist id="r-paises">@foreach ($paises as $p)<option value="{{ $p }}">@endforeach</datalist>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="r-clave">Contraseña</label>
                        <div class="im-clave">
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="r-clave" name="password" required minlength="8" autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                            <button type="button" class="im-ver-clave" data-ver-clave aria-label="Mostrar contraseña"><i class="bx bx-hide"></i></button>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="r-clave2">Repita la contraseña</label>
                        <input type="password" class="form-control" id="r-clave2" name="password_confirmation" required minlength="8" autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" class="im-btn im-btn-primario w-100"><i class="bx bx-user-plus"></i> Crear mi cuenta</button>

                <p class="im-cuenta-pie">¿Ya tiene cuenta? <a href="{{ route('login') }}">Inicie sesión</a></p>
            </form>
        </div>
    </section>
@endsection
