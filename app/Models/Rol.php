<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'descripcion', 'acceso_total', 'permisos'])]
class Rol extends Model
{
    protected $table = 'roles';

    /** Módulos del sistema que se pueden dar a un rol */
    public const PERMISOS = [
        'tablero' => ['Tablero', 'Ver el resumen de ventas'],
        'pedidos' => ['Pedidos', 'Revisar pagos, enviar los accesos y rechazar pedidos'],
        'cursos' => ['Cursos', 'Agregar y editar cursos, precios, videos y links de pago'],
        'reportes' => ['Reportes', 'Ventas por mes, por curso y por medio de pago'],
        'configuracion' => ['Configuración', 'Datos de la empresa, contacto y cuentas para pagar'],
        'usuarios' => ['Usuarios y roles', 'Crear usuarios y roles'],
    ];

    protected function casts(): array
    {
        return ['acceso_total' => 'boolean', 'permisos' => 'array'];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function permite(string $permiso): bool
    {
        return $this->acceso_total || in_array($permiso, $this->permisos ?? [], true);
    }
}
