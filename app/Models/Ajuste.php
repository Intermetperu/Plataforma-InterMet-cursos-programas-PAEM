<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Ajustes que se cambian desde Admin › Configuración. Pisan a config/cursos.php */
#[Fillable(['clave', 'valor'])]
class Ajuste extends Model
{
    protected $table = 'ajustes';

    public static function guardar(string $clave, mixed $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : $valor]);
        config(["cursos.{$clave}" => $valor]);
    }

    /** Pasa los ajustes guardados a config('cursos.*'). Si aún no se migró, no hace nada. */
    public static function cargarEnConfig(): void
    {
        try {
            if (! Schema::hasTable('ajustes')) {
                return;
            }
            foreach (static::all() as $a) {
                config(["cursos.{$a->clave}" => $a->valor]);
            }
        } catch (Throwable) {
            // Sin base de datos: se usan los valores de config/cursos.php
        }
    }

    /** Texto con una cosa por línea -> lista */
    public static function lineas(?string $texto): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $texto))));
    }
}
