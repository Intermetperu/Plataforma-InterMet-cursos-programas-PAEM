@extends('layouts.public')

@section('title', 'Iniciar sesión')

@section('content')
    <section class="im-cabecera-simple">
        <div class="container">
            <nav aria-label="Ruta" class="im-migas"><a href="{{ route('home') }}">Cursos digitales</a><i class="bx bx-chevron-right"></i><span>Iniciar sesión</span></nav>
            <h1>Iniciar sesión</h1>
            <p>Ingrese con su correo y contraseña.</p>
        </div>
    </section>

    <section class="im-sobre">
        <div class="container im-cuenta-caja">
            <form method="POST" action="{{ route('login.store') }}" class="im-form-bloque im-cuenta" novalidate>
                @csrf
                <div class="im-cuenta-tabs" role="tablist">
                    <a href="{{ route('login') }}" class="activo" aria-current="page">Iniciar sesión</a>
                    <a href="{{ route('registro') }}">Registrarse</a>
                </div>

                @if ($errors->any())
                    <div class="im-alerta error" role="alert"><i class="bx bx-error-circle"></i><div>{{ $errors->first() }}</div></div>
                @endif

                <div class="mb-3">
                    <label class="form-label" for="email">Correo electrónico</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="usted@correo.com">
                </div>
                <div class="mb-2">
                    <label class="form-label" for="password">Contraseña</label>
                    <div class="im-clave">
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" placeholder="Su contraseña">
                        <button type="button" class="im-ver-clave" data-ver-clave aria-label="Mostrar contraseña"><i class="bx bx-hide"></i></button>
                    </div>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="recordar" name="recordar" value="1">
                    <label class="form-check-label" for="recordar">Mantener la sesión iniciada</label>
                </div>
                <button type="submit" class="im-btn im-btn-primario w-100"><i class="bx bx-log-in"></i> Iniciar sesión</button>

                <p class="im-cuenta-pie">¿Aún no tiene cuenta? <a href="{{ route('registro') }}">Regístrese gratis</a></p>
            </form>
        </div>
    </section>
@endsection
