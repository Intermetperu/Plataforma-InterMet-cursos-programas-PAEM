<?php

use App\Http\Controllers\Admin\ConfiguracionController;
use App\Http\Controllers\Admin\CursoController as AdminCursoController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PedidoController as AdminPedidoController;
use App\Http\Controllers\Admin\ProgramaController as AdminProgramaController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ProgramaController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\PedidoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web pública: catálogo de cursos, detalle y registro del pago
|--------------------------------------------------------------------------
*/
Route::get('/', [CursoController::class, 'index'])->name('home');
Route::get('/cursos-digitales', [CursoController::class, 'index'])->name('cursos.index');
Route::get('/cursos-digitales/{curso:slug}', [CursoController::class, 'show'])->name('cursos.show');

// Programas de alta especialización (PAEM) y carrito
Route::get('/programas', [ProgramaController::class, 'index'])->name('programas.index');
Route::get('/programas/{programa:slug}', [ProgramaController::class, 'show'])->name('programas.show');
Route::get('/carrito', [CarritoController::class, 'index'])->name('carrito.index');
Route::post('/carrito/{programa}', [CarritoController::class, 'agregar'])->middleware('throttle:60,1')->name('carrito.agregar');
Route::delete('/carrito/{programa}', [CarritoController::class, 'quitar'])->name('carrito.quitar');
Route::delete('/carrito', [CarritoController::class, 'vaciar'])->name('carrito.vaciar');

// Registrar el pago (en el hotel era «reservar»): datos, cursos, constancia
Route::get('/registrar-pago', [PedidoController::class, 'create'])->name('pedido.create');
Route::post('/registrar-pago', [PedidoController::class, 'store'])->middleware('throttle:registrar-pago')->name('pedido.store');
Route::get('/pedido/{token}', [PedidoController::class, 'estado'])->name('pedido.estado');
Route::get('/mi-pedido', [PedidoController::class, 'consulta'])->name('pedido.consulta');
Route::post('/mi-pedido', [PedidoController::class, 'buscar'])->middleware('throttle:consultar-pedido')->name('pedido.buscar');

// Asistente (chat del panel lateral)
Route::get('/asistente', [ChatbotController::class, 'bienvenida'])->middleware('throttle:chatbot')->name('chatbot.bienvenida');
Route::post('/asistente', [ChatbotController::class, 'responder'])->middleware('throttle:chatbot')->name('chatbot.responder');

/*
|--------------------------------------------------------------------------
| Iniciar / cerrar sesión
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:ingreso')->name('login.store');
    Route::get('/registrarse', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registrarse', [RegistroController::class, 'store'])->middleware('throttle:registro')->name('registro.store');
});
Route::get('/mis-pedidos', [PedidoController::class, 'mios'])->middleware('auth')->name('pedido.mios');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Panel de administración
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'personal'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Pedidos: verificar el pago, enviar el acceso, rechazar
    Route::middleware('permiso:pedidos')->group(function () {
        Route::get('/pedidos', [AdminPedidoController::class, 'index'])->name('pedidos.index');
        Route::get('/pedidos/{pedido}', [AdminPedidoController::class, 'show'])->name('pedidos.show');
        Route::get('/pedidos/{pedido}/captura', [AdminPedidoController::class, 'captura'])->name('pedidos.captura');
        Route::post('/pedidos/{pedido}/verificar', [AdminPedidoController::class, 'verificar'])->name('pedidos.verificar');
        Route::post('/pedidos/{pedido}/enviar', [AdminPedidoController::class, 'enviar'])->name('pedidos.enviar');
        Route::post('/pedidos/{pedido}/rechazar', [AdminPedidoController::class, 'rechazar'])->name('pedidos.rechazar');
        Route::post('/pedidos/{pedido}/reabrir', [AdminPedidoController::class, 'reabrir'])->name('pedidos.reabrir');
    });

    // Cursos: agregar, editar, publicar, importar desde Excel
    Route::middleware('permiso:cursos')->group(function () {
        Route::get('/cursos/importar', [AdminCursoController::class, 'importarForm'])->name('cursos.importar');
        Route::post('/cursos/importar', [AdminCursoController::class, 'importar'])->name('cursos.importar.store');
        Route::post('/cursos/importar-hoja', [AdminCursoController::class, 'importarHoja'])->name('cursos.importar.hoja');
        Route::post('/cursos/importar-imagenes', [AdminCursoController::class, 'importarImagenes'])->name('cursos.importar.imagenes');
        Route::get('/cursos/plantilla', [AdminCursoController::class, 'plantilla'])->name('cursos.plantilla');
        Route::post('/cursos/{curso}/publicar', [AdminCursoController::class, 'publicar'])->name('cursos.publicar');
        Route::resource('cursos', AdminCursoController::class)->except('show');
        Route::post('/programas/{programa}/publicar', [AdminProgramaController::class, 'publicar'])->name('programas.publicar');
        Route::resource('programas', AdminProgramaController::class)->except(['show', 'destroy']);
    });

    Route::get('/reportes', [ReporteController::class, 'index'])->middleware('permiso:reportes')->name('reportes');

    Route::middleware('permiso:configuracion')->group(function () {
        Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion');
        Route::post('/configuracion', [ConfiguracionController::class, 'guardar'])->name('configuracion.guardar');
    });
    Route::post('/configuracion/clave', [ConfiguracionController::class, 'clave'])->name('configuracion.clave');

    Route::middleware('permiso:usuarios')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
        Route::post('/roles', [UsuarioController::class, 'guardarRol'])->name('roles.store');
        Route::put('/roles/{rol}', [UsuarioController::class, 'actualizarRol'])->name('roles.update');
        Route::delete('/roles/{rol}', [UsuarioController::class, 'eliminarRol'])->name('roles.destroy');
    });
});
