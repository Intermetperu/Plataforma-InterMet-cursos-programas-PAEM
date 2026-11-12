<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/** Programa de Alta Especialización Minera (PAEM): un paquete de cursos con su propio precio y link de pago */
#[Fillable(['numero', 'slug', 'nombre', 'subtitulo', 'area', 'titulo', 'descripcion', 'temario', 'precio', 'link_pago', 'color', 'icono', 'imagen', 'galeria', 'fotos_cursos', 'publicado', 'orden'])]
class Programa extends Model
{
    public const COLORES = ['naranja' => 'Naranja', 'azul' => 'Azul', 'rojo' => 'Rojo', 'marron' => 'Marrón', 'verde' => 'Verde', 'morado' => 'Morado', 'dorado' => 'Dorado'];

    /** Áreas para filtrar: clave => [nombre, ícono] */
    public const AREAS = [
        'flotacion' => ['Flotación', 'bx-water'],
        'molienda' => ['Molienda y chancado', 'bx-cog'],
        'oro' => ['Oro y fundición', 'bx-diamond'],
        'hidro' => ['Hidrometalurgia y litio', 'bx-vial'],
        'cobre' => ['Cobre', 'bx-cube'],
        'relaves' => ['Relaves y ambiental', 'bx-droplet'],
        'muestreo' => ['Muestreo y geología', 'bx-target-lock'],
        'simulacion' => ['Simulación y software', 'bx-sitemap'],
        'digital' => ['Digital, IA y minería 5.0', 'bx-chip'],
        'operacion' => ['Proyectos y operación', 'bx-buildings'],
    ];

    protected function casts(): array
    {
        return ['precio' => 'decimal:2', 'publicado' => 'boolean', 'galeria' => 'array', 'fotos_cursos' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Programa $p) {
            if (! $p->slug) {
                $p->slug = Str::limit(($p->numero ? $p->numero . '-' : '') . Str::slug($p->titulo), 180, '');
            }
        });
    }

    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Curso::class, 'curso_programa')->withPivot('orden')->orderBy('curso_programa.orden');
    }

    public function scopePublicados(Builder $q): Builder
    {
        return $q->where('publicado', true)->orderBy('orden')->orderBy('numero');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getUrlAttribute(): string
    {
        return route('programas.show', $this);
    }

    /** Lo que costarían los cursos comprados uno por uno (precio regular) */
    public function getValorSeparadoAttribute(): float
    {
        return round((float) $this->cursos->where('precio', '>', 0)->sum('precio'), 2);
    }

    /** % de ahorro frente a comprar los cursos por separado (0 si no aplica) */
    public function getAhorroAttribute(): int
    {
        $valor = $this->valor_separado;

        return $valor > (float) $this->precio ? (int) floor(100 - (float) $this->precio * 100 / $valor) : 0;
    }

    public function getHorasAttribute(): float
    {
        return (float) $this->cursos->sum('horas');
    }

    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen ? (Str::startsWith($this->imagen, 'http') ? $this->imagen : asset($this->imagen)) : null;
    }

    /** Imágenes para la galería: la del programa y las de sus cursos */
    public function getFotosAttribute(): array
    {
        // Portada, luego las miniaturas subidas en el panel, luego las fotos de sus cursos (YouTube al final)
        $fotos = $this->imagen_url ? [['src' => $this->imagen_url, 'alt' => $this->titulo]] : [];
        foreach ((array) $this->galeria as $ruta) {
            $fotos[] = ['src' => Str::startsWith($ruta, 'http') ? $ruta : asset($ruta), 'alt' => $this->titulo];
        }
        $videos = [];
        foreach ($this->fotos_cursos ? $this->cursos : [] as $c) {
            if ($c->imagen) {
                $fotos[] = ['src' => $c->imagen_url, 'alt' => $c->titulo];
            } elseif ($c->youtube_id) {
                $videos[] = ['src' => "https://i.ytimg.com/vi/{$c->youtube_id}/hqdefault.jpg", 'alt' => $c->titulo];
            }
        }

        return array_values(array_unique([...$fotos, ...$videos], SORT_REGULAR));
    }

    /**
     * Temario por módulos: [['titulo' => …, 'temas' => […]], …]
     * Del texto del panel («# Módulo» = título, cada línea = tema) o, si está vacío, un módulo por curso.
     */
    public function getModulosAttribute(): array
    {
        $texto = trim((string) $this->temario);
        if ($texto === '') {
            return $this->cursos->values()->map(fn ($c, $i) => [
                'titulo' => 'Módulo ' . ($i + 1) . ': ' . Str::ucfirst(Str::lower($c->titulo)),
                'temas' => array_values(array_filter(array_map('trim', preg_split('/\R|(?<=[.?])\s+(?=[A-ZÁÉÍÓÚ¿])/u', (string) $c->temario)))),
                'detalle' => trim(($c->instructor ? $c->instructor : '') . ($c->horas ? ' · ' . rtrim(rtrim(number_format((float) $c->horas, 1), '0'), '.') . ' h de video' : ''), ' ·'),
            ])->all();
        }
        $modulos = [];
        foreach (preg_split('/\R/u', $texto) as $linea) {
            $linea = trim($linea);
            if ($linea === '') {
                continue;
            }
            if (str_starts_with($linea, '#')) {
                $modulos[] = ['titulo' => trim(ltrim($linea, '# ')), 'temas' => [], 'detalle' => ''];
            } else {
                if (! $modulos) {
                    $modulos[] = ['titulo' => 'Temario', 'temas' => [], 'detalle' => ''];
                }
                $modulos[count($modulos) - 1]['temas'][] = ltrim($linea, '-•* ');
            }
        }

        return $modulos;
    }

    public function getTextoTarjetaAttribute(): string
    {
        return 'Programa de alta especialización en ' . Str::lower($this->titulo);
    }
}
