<?php

namespace App\Support;

use App\Models\Programa;
use Illuminate\Support\Collection;

/** Carrito de programas (PAEM), guardado en la sesión del visitante */
class Carrito
{
    private const CLAVE = 'carrito_programas';

    /** @return array<int> */
    public static function ids(): array
    {
        return array_values(array_unique(array_map('intval', (array) session(self::CLAVE, []))));
    }

    public static function cantidad(): int
    {
        return count(self::ids());
    }

    public static function tiene(Programa $p): bool
    {
        return in_array($p->id, self::ids(), true);
    }

    public static function agregar(Programa $p): void
    {
        session([self::CLAVE => array_values(array_unique([...self::ids(), $p->id]))]);
    }

    public static function quitar(Programa $p): void
    {
        session([self::CLAVE => array_values(array_diff(self::ids(), [$p->id]))]);
    }

    public static function vaciar(): void
    {
        session()->forget(self::CLAVE);
    }

    /** Programas del carrito (solo los publicados), en el orden en que se agregaron */
    public static function programas(): Collection
    {
        $ids = self::ids();
        if (! $ids) {
            return collect();
        }

        return Programa::whereIn('id', $ids)->where('publicado', true)->with('cursos')->get()
            ->sortBy(fn ($p) => array_search($p->id, $ids))->values();
    }
}
