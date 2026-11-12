<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'celular', 'pais', 'role_id', 'activo', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'role_id');
    }

    /** Personal de InterMet (tiene rol) -> entra al panel. Sin rol = cliente de la web. */
    public function esPersonal(): bool
    {
        return $this->role_id !== null;
    }

    /** Página a la que va después de iniciar sesión */
    public function inicio(): string
    {
        return $this->esPersonal() ? route('admin.dashboard') : route('home');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    /** Administrador = rol con acceso total */
    public function esAdmin(): bool
    {
        return (bool) $this->role?->acceso_total;
    }

    /** ¿Puede usar este módulo? (ver Rol::PERMISOS) */
    public function puede(string $permiso): bool
    {
        return (bool) $this->role?->permite($permiso);
    }

    public function getNombreRolAttribute(): string
    {
        return $this->role?->nombre ?? 'Cliente';
    }
}
