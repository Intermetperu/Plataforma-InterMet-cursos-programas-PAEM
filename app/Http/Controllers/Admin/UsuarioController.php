<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Usuarios del panel y roles con permisos.
 */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Rol::withCount('usuarios')->orderByDesc('acceso_total')->orderBy('nombre')->get();
        // Personal (con rol). Los clientes que se registran en la web van en su propia pestaña.
        $usuarios = User::with('role')->whereNotNull('role_id')->orderBy('name')->get();
        $clientes = User::whereNull('role_id')->withCount('pedidos')->latest()->limit(300)->get();

        return view('admin.usuarios.index', [
            'tab' => $request->query('tab', 'usuarios'),
            'usuarios' => $usuarios,
            'clientes' => $clientes,
            'roles' => $roles,
            'permisos' => Rol::PERMISOS,
        ]);
    }

    // ---------------- Usuarios ----------------
    private function reglasUsuario(?User $u = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($u?->id)],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => [$u ? 'nullable' : 'required', 'string', 'min:8'],
        ];
    }

    private function nombres(): array
    {
        return ['name' => 'nombre', 'email' => 'correo', 'role_id' => 'rol', 'password' => 'contraseña'];
    }

    private function volver(string $tab, string $mensaje, string $tipo = 'ok'): RedirectResponse
    {
        return redirect()->route('admin.usuarios.index', ['tab' => $tab])->with($tipo, $mensaje);
    }

    /** ¿Quedaría el sistema sin ningún usuario activo con acceso total? */
    private function quedariaSinAdmin(?User $cambiando, ?int $nuevoRol, bool $activo): bool
    {
        $admins = User::where('activo', true)->whereHas('role', fn ($q) => $q->where('acceso_total', true))->pluck('id');
        $seguiraSiendo = $nuevoRol && Rol::whereKey($nuevoRol)->value('acceso_total') && $activo;

        return $cambiando && $admins->contains($cambiando->id) && $admins->count() === 1 && ! $seguiraSiendo;
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglasUsuario(), [], $this->nombres());
        $datos['activo'] = true;
        User::create($datos);

        return $this->volver('usuarios', "Usuario {$datos['name']} creado.");
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $request->validate($this->reglasUsuario($usuario), [], $this->nombres());
        $datos['activo'] = $usuario->is($request->user()) ? true : $request->boolean('activo');

        if ($this->quedariaSinAdmin($usuario, (int) $datos['role_id'], $datos['activo'])) {
            return $this->volver('usuarios', 'Debe quedar al menos un administrador activo.', 'error');
        }
        if (empty($datos['password'])) {
            unset($datos['password']);
        }
        $usuario->update($datos);

        return $this->volver('usuarios', "Usuario {$usuario->name} actualizado.");
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return $this->volver('usuarios', 'No puedes eliminar tu propio usuario.', 'error');
        }
        if ($this->quedariaSinAdmin($usuario, null, false)) {
            return $this->volver('usuarios', 'Debe quedar al menos un administrador activo.', 'error');
        }
        // Si ya atendió pedidos no se borra: se desactiva para no perder el historial
        if (\App\Models\Pedido::where('atendido_por', $usuario->id)->exists()) {
            $usuario->update(['activo' => false]);

            return $this->volver('usuarios', "{$usuario->name} atendió pedidos: se desactivó en lugar de eliminarlo (ya no puede entrar).");
        }
        $nombre = $usuario->name;
        $usuario->delete();

        return $this->volver('usuarios', "Usuario {$nombre} eliminado.");
    }

    // ---------------- Roles ----------------
    private function reglasRol(?Rol $rol = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:60', Rule::unique('roles', 'nombre')->ignore($rol?->id)],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => [Rule::in(array_keys(Rol::PERMISOS))],
        ];
    }

    public function guardarRol(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglasRol(), [], ['nombre' => 'nombre del rol']);
        $datos['permisos'] = array_values($datos['permisos'] ?? []);
        $datos['acceso_total'] = false;
        Rol::create($datos);

        return $this->volver('roles', "Rol {$datos['nombre']} creado.");
    }

    public function actualizarRol(Request $request, Rol $rol): RedirectResponse
    {
        $datos = $request->validate($this->reglasRol($rol), [], ['nombre' => 'nombre del rol']);
        // El rol con acceso total no cambia sus permisos (siempre tiene todo)
        $datos['permisos'] = $rol->acceso_total ? [] : array_values($datos['permisos'] ?? []);

        if (! $rol->acceso_total && $rol->usuarios()->whereKey($request->user()->id)->exists() && ! in_array('usuarios', $datos['permisos'], true)) {
            return $this->volver('roles', 'No puedes quitarte a ti mismo el permiso «Usuarios y roles».', 'error');
        }
        $rol->update($datos);

        return $this->volver('roles', "Rol {$rol->nombre} actualizado.");
    }

    public function eliminarRol(Rol $rol): RedirectResponse
    {
        if ($rol->acceso_total) {
            return $this->volver('roles', 'El rol Administrador no se puede eliminar.', 'error');
        }
        if ($rol->usuarios()->exists()) {
            return $this->volver('roles', "El rol {$rol->nombre} tiene usuarios asignados. Cámbialos de rol primero.", 'error');
        }
        $nombre = $rol->nombre;
        $rol->delete();

        return $this->volver('roles', "Rol {$nombre} eliminado.");
    }
}
