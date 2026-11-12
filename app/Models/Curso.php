<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'numero', 'slug', 'titulo', 'tipo', 'idioma', 'anio', 'fecha_publicacion', 'precio', 'precio_oferta', 'en_oferta',
    'horas', 'incluye', 'instructor', 'instructor_bio', 'resumen', 'dirigido_a', 'temario', 'video_url', 'imagen',
    'link_pago', 'enlaces_descarga', 'publicado', 'destacado', 'vendidos',
])]
class Curso extends Model
{
    public const IDIOMAS = ['es' => 'Español', 'en' => 'English'];

    /** Portadas automáticas según el tema (cuando el curso no tiene video ni imagen) */
    private const TEMAS = [
        'flotacion' => ['Flotación', 'flot', 'bx-water'],
        'flotation' => ['Flotación', 'flot', 'bx-water'],
        'molienda' => ['Conminución', 'moli', 'bx-cog'],
        'conminuc' => ['Conminución', 'moli', 'bx-cog'],
        'comminution' => ['Conminución', 'moli', 'bx-cog'],
        'chancad' => ['Conminución', 'moli', 'bx-cog'],
        'sag' => ['Conminución', 'moli', 'bx-cog'],
        'mine to mill' => ['Mine to Mill', 'moli', 'bx-cog'],
        'muestreo' => ['Muestreo', 'mues', 'bx-vial'],
        'sampling' => ['Muestreo', 'mues', 'bx-vial'],
        'machine learning' => ['Machine Learning', 'dig', 'bx-chip'],
        'digital' => ['Transformación digital', 'dig', 'bx-chip'],
        'mineria 4' => ['Minería digital', 'dig', 'bx-chip'],
        'mineria 5' => ['Minería digital', 'dig', 'bx-chip'],
        'automatiz' => ['Automatización', 'dig', 'bx-chip'],
        'hidrometal' => ['Hidrometalurgia', 'hid', 'bx-test-tube'],
        'hydrometal' => ['Hidrometalurgia', 'hid', 'bx-test-tube'],
        'electrometal' => ['Hidrometalurgia', 'hid', 'bx-test-tube'],
        'lixivia' => ['Hidrometalurgia', 'hid', 'bx-test-tube'],
        'heap leaching' => ['Hidrometalurgia', 'hid', 'bx-test-tube'],
        'litio' => ['Hidrometalurgia', 'hid', 'bx-test-tube'],
        'relave' => ['Relaves y aguas', 'agua', 'bx-droplet'],
        'aguas' => ['Relaves y aguas', 'agua', 'bx-droplet'],
        'goldsim' => ['Relaves y aguas', 'agua', 'bx-droplet'],
        'oro' => ['Oro y plata', 'oro', 'bx-diamond'],
        'gold' => ['Oro y plata', 'oro', 'bx-diamond'],
        'carbon activado' => ['Oro y plata', 'oro', 'bx-diamond'],
        'fundicion' => ['Oro y plata', 'oro', 'bx-diamond'],
        'cobre' => ['Cobre', 'cobre', 'bx-cube-alt'],
        'copper' => ['Cobre', 'cobre', 'bx-cube-alt'],
        'metsim' => ['Simulación', 'sim', 'bx-line-chart'],
        'simulac' => ['Simulación', 'sim', 'bx-line-chart'],
        'software' => ['Simulación', 'sim', 'bx-line-chart'],
        'minitab' => ['Estadística', 'est', 'bx-bar-chart-alt-2'],
        'estadist' => ['Estadística', 'est', 'bx-bar-chart-alt-2'],
        'six sigma' => ['Estadística', 'est', 'bx-bar-chart-alt-2'],
        'experimentos' => ['Estadística', 'est', 'bx-bar-chart-alt-2'],
        'balance' => ['Balances', 'est', 'bx-bar-chart-alt-2'],
        'geometal' => ['Geometalurgia', 'geo', 'bx-layer'],
        'geolog' => ['Geometalurgia', 'geo', 'bx-layer'],
        'proyecto' => ['Proyectos', 'proy', 'bx-buildings'],
        'comisionamiento' => ['Proyectos', 'proy', 'bx-buildings'],
        'diseno' => ['Diseño de plantas', 'proy', 'bx-buildings'],
        'dimensionamiento' => ['Diseño de plantas', 'proy', 'bx-buildings'],
    ];

    protected function casts(): array
    {
        return [
            'fecha_publicacion' => 'date',
            'precio' => 'decimal:2',
            'precio_oferta' => 'decimal:2',
            'horas' => 'decimal:1',
            'en_oferta' => 'boolean',
            'publicado' => 'boolean',
            'destacado' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Curso $c) {
            $c->tipo = preg_match('/congres|encuentro|conferen|expocobre|keynote|conference|flotation 21/i', $c->titulo) ? 'congreso' : 'curso';
            if (! $c->slug) {
                $c->slug = static::slugLibre(trim(($c->numero ? $c->numero . '-' : '') . Str::slug(Str::limit($c->titulo, 120, ''))), $c->id);
            }
            if (! $c->anio && $c->fecha_publicacion) {
                $c->anio = $c->fecha_publicacion->year;
            }
        });
    }

    public static function slugLibre(string $base, ?int $sinId = null): string
    {
        $slug = $base;
        for ($i = 2; static::where('slug', $slug)->when($sinId, fn ($q) => $q->where('id', '!=', $sinId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublicados(Builder $q): Builder
    {
        return $q->where('publicado', true);
    }

    /** Precio que paga hoy el cliente (USD) */
    public function getPrecioActualAttribute(): float
    {
        return (float) ($this->en_oferta && $this->precio_oferta ? $this->precio_oferta : $this->precio);
    }

    public function getNumeroTextoAttribute(): string
    {
        return $this->numero ? '#' . str_pad((string) $this->numero, 2, '0', STR_PAD_LEFT) : '';
    }

    /** ID del video de YouTube (sirve con youtu.be/…, watch?v=…, embed/… o el ID solo) */
    public function getYoutubeIdAttribute(): ?string
    {
        $url = trim((string) $this->video_url);
        if ($url === '') {
            return null;
        }
        if (preg_match('~(?:youtu\.be/|v=|embed/|shorts/|live/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }

        return preg_match('~^[A-Za-z0-9_-]{11}$~', $url) ? $url : null;
    }

    public function getImagenUrlAttribute(): ?string
    {
        if ($this->imagen) {
            return asset($this->imagen);
        }

        return $this->youtube_id ? "https://i.ytimg.com/vi/{$this->youtube_id}/hqdefault.jpg" : null;
    }

    /** Tema para la portada automática: [nombre, clase de color, icono] */
    public function getTemaAttribute(): array
    {
        $t = ' ' . Str::lower(Str::ascii($this->titulo . ' ' . $this->incluye)) . ' ';
        foreach (self::TEMAS as $clave => $tema) {
            if (str_contains($t, $clave)) {
                return $tema;
            }
        }

        return ['Procesamiento de minerales', 'gen', 'bx-atom'];
    }

    public function getDirigidoListaAttribute(): array
    {
        return Ajuste::lineas($this->dirigido_a ?: config('cursos.dirigido_a'));
    }

    /** Temario: una línea por tema. «(2 horas)» al final se muestra aparte. */
    public function getTemarioListaAttribute(): array
    {
        return array_map(function ($linea) {
            $duracion = null;
            if (preg_match('/\(\s*([\d.,]+\s*(?:h|hr|hrs|hora|horas))\.?\s*\)\.?\s*$/iu', $linea, $m)) {
                $duracion = $m[1];
                $linea = trim(substr($linea, 0, -strlen($m[0])));
            }

            return ['texto' => ltrim($linea, "•-–* \t"), 'duracion' => $duracion];
        }, Ajuste::lineas($this->temario));
    }

    /** ¿Salió en el último año del catálogo? (para el sello «Nuevo») */
    public function getEsNuevoAttribute(): bool
    {
        static $ultimo = null;
        $ultimo ??= (int) static::publicados()->max('anio');

        return $this->anio && $this->anio >= $ultimo;
    }

    public function getUrlAttribute(): string
    {
        return route('cursos.show', $this);
    }
}
