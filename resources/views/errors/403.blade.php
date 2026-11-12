<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin permiso</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f5f7fb; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color: #212529; padding: 16px; }
        .caja { background: #fff; border-radius: 12px; padding: 32px; max-width: 460px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,.06); }
        h1 { font-size: 22px; margin: 0 0 8px; }
        p { color: #6c757d; line-height: 1.5; }
        a { display: inline-block; margin-top: 8px; padding: 10px 18px; background: #0d6efd; color: #fff; border-radius: 8px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="caja">
        <h1>No tienes permiso</h1>
        <p>{{ $exception->getMessage() ?: 'Tu rol no tiene acceso a esta parte del sistema.' }}</p>
        <a href="{{ auth()->check() ? route('admin.dashboard') : url('/') }}">Volver</a>
    </div>
</body>
</html>
