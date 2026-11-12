<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Catálogo público: búsqueda, filtro por año en que salió, idioma, orden y páginas */
class CursoController extends Controller
{
    public const ORDENES = [
        'numero' => 'N° de catálogo (01, 02, 03…)',
        'recientes' => 'Más recientes',
        'antiguos' => 'Más antiguos',
        'precio_menor' => 'Precio: menor a mayor',
        'precio_mayor' => 'Precio: mayor a menor',
    ];

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $anio = $request->query('anio');
        $idioma = $request->query('idioma');
        $tipo = $request->query('tipo');
        $orden = array_key_exists($request->query('orden'), self::ORDENES) ? $request->query('orden') : 'numero';

        // Filtros que no son el año (para contar cuántos hay en cada año)
        $base = Curso::publicados()
            ->when($q !== '', fn (Builder $c) => $this->buscar($c, $q))
            ->when(isset(Curso::IDIOMAS[$idioma]), fn (Builder $c) => $c->where('idioma', $idioma))
            ->when(in_array($tipo, ['curso', 'congreso'], true), fn (Builder $c) => $c->where('tipo', $tipo));

        $porAnio = (clone $base)->selectRaw('anio, count(*) as total')->groupBy('anio')->orderByDesc('anio')->pluck('total', 'anio');

        $cursos = (clone $base)
            ->when($anio === 'sin', fn (Builder $c) => $c->whereNull('anio'))
            ->when(is_numeric($anio), fn (Builder $c) => $c->where('anio', (int) $anio))
            ->tap(fn (Builder $c) => $this->ordenar($c, $orden))
            ->paginate((int) config('cursos.por_pagina', 12))
            ->withQueryString()
            ->fragment('catalogo');

        return view('cursos.index', [
            'cursos' => $cursos,
            'porAnio' => $porAnio,
            'filtros' => compact('q', 'anio', 'idioma', 'tipo', 'orden'),
            'ordenes' => self::ORDENES,
            'totalCatalogo' => Curso::publicados()->count(),
            'rangoAnios' => [Curso::publicados()->min('anio'), Curso::publicados()->max('anio')],
            'programas' => $programas = \App\Models\Programa::publicados()->with('cursos:id,precio,horas')->get(),
            // 3 programas para la ventana de bienvenida: los de mayor ahorro, de áreas distintas
            'destacados' => $programas->sortByDesc('ahorro')->unique('area')->take(3)->values(),
        ]);
    }

    public function show(Curso $curso): View
    {
        abort_unless($curso->publicado || auth()->check(), 404);

        $relacionados = Curso::publicados()->whereKeyNot($curso->id)
            ->when($curso->instructor, fn (Builder $c) => $c->where('instructor', $curso->instructor), fn (Builder $c) => $c->where('anio', $curso->anio))
            ->orderByDesc('anio')->limit(3)->get();
        if ($relacionados->count() < 3) {
            $relacionados = $relacionados->concat(
                Curso::publicados()->whereKeyNot($curso->id)->whereNotIn('id', $relacionados->pluck('id'))
                    ->where('anio', $curso->anio)->orderByDesc('numero')->limit(3 - $relacionados->count())->get()
            );
        }

        // La versión en el otro idioma (mismo N°), si existe
        $otraVersion = $curso->numero
            ? Curso::publicados()->where('numero', $curso->numero)->where('idioma', '!=', $curso->idioma)->first()
            : null;

        return view('cursos.show', compact('curso', 'relacionados', 'otraVersion'));
    }

    /** Cada palabra debe aparecer en el título, el instructor, lo que incluye o el N° */
    private function buscar(Builder $c, string $q): Builder
    {
        foreach (array_slice(preg_split('/\s+/', $q), 0, 6) as $palabra) {
            $palabra = ltrim($palabra, '#');
            if ($palabra === '') {
                continue;
            }
            $c->where(function (Builder $w) use ($palabra) {
                $w->where('titulo', 'like', "%{$palabra}%")
                    ->orWhere('instructor', 'like', "%{$palabra}%")
                    ->orWhere('incluye', 'like', "%{$palabra}%");
                if (ctype_digit($palabra)) {
                    $w->orWhere('numero', (int) $palabra)->orWhere('anio', (int) $palabra);
                }
            });
        }

        return $c;
    }

    private function ordenar(Builder $c, string $orden): void
    {
        $precio = 'CASE WHEN en_oferta = 1 AND precio_oferta IS NOT NULL THEN precio_oferta ELSE precio END';
        match ($orden) {
            'antiguos' => $c->orderByRaw('anio IS NULL')->orderBy('anio')->orderBy('numero'),
            'precio_menor' => $c->orderByRaw($precio . ' asc')->orderByDesc('anio'),
            'precio_mayor' => $c->orderByRaw($precio . ' desc')->orderByDesc('anio'),
            'numero' => $c->orderBy('numero')->orderBy('idioma'),
            default => $c->orderByRaw('anio IS NULL')->orderByDesc('anio')->orderByDesc('fecha_publicacion')->orderByDesc('numero'),
        };
    }
}
