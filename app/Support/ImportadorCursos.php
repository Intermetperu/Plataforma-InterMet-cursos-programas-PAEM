<?php

namespace App\Support;

use App\Models\Curso;
use Illuminate\Support\Str;

/**
 * Pasa las filas de un Excel / CSV a la tabla de cursos.
 * Reconoce las columnas por su título (N°, Título, Año, Oferta, Regular, Software,
 * Instructor, Link de pago, Video, Horas…), en cualquier orden.
 * Un curso existente se reconoce por su N° y su título; solo se cambian las celdas con datos.
 */
class ImportadorCursos
{
    /** campo => palabras con las que puede venir el título de la columna */
    private const COLUMNAS = [
        'numero' => ['n', 'no', 'nro', 'num', 'numero', '#', 'n°', 'nº', 'item', 'codigo'],
        'titulo' => ['titulo', 'titulos', 'curso', 'cursos', 'nombre', 'nombre del curso', 'titulo del curso'],
        'anio' => ['ano', 'anio', 'year', 'año', 'ano de realizacion', 'año de realizacion', 'ano de publicacion'],
        'fecha_publicacion' => ['fecha', 'fecha de publicacion', 'publicado el', 'fecha publicacion'],
        'precio_oferta' => ['oferta', 'precio oferta', 'precio de oferta', 'descuento'],
        'precio' => ['regular', 'precio regular', 'precio', 'inversion', 'precio usd', 'usd'],
        'incluye' => ['incluye software', 'incluye software?', 'software', 'incluye'],
        'instructor' => ['instructor', 'instructores', 'instructor(es)', 'instructor es', 'expositor', 'expositores', 'docente', 'ponente'],
        'link_pago' => ['link', 'link de pago', 'links de pago', 'enlace de pago', 'url de pago', 'pago', 'niubiz', 'link pago', 'comprar', 'pagolink'],
        'video_url' => ['video', 'youtube', 'video youtube', 'link del video', 'presentacion', 'video de presentacion'],
        'enlaces_descarga' => ['videos', 'materiales', 'material', 'descarga', 'descargas', 'enlace de descarga', 'enlaces de descarga', 'links de descarga', 'wetransfer', 'enlace', 'enlaces'],
        'horas' => ['horas', 'duracion', 'horas de video'],
        'idioma' => ['idioma', 'language'],
        'resumen' => ['resumen', 'descripcion'],
        'dirigido_a' => ['dirigido a', 'publico', 'dirigido'],
        'temario' => ['temario', 'contenido'],
        'instructor_bio' => ['bio', 'biografia', 'perfil del instructor', 'sobre el instructor'],
        'publicado' => ['publicado', 'visible', 'activo'],
    ];

    public array $resumen = ['creados' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'omitidos' => [], 'columnas' => []];

    public function __construct(private bool $crearNuevos = true)
    {
    }

    public static function normalizar(string $t): string
    {
        return trim(preg_replace('/\s+/', ' ', Str::lower(Str::ascii(str_replace(['°', 'º'], ' ', $t)))), " :.?¿");
    }

    /** «$72,00» · «USD 1,200» · «1.200,50» · «72.5» → número */
    public static function precio(?string $t): ?float
    {
        $t = preg_replace('/[^\d.,]/', '', (string) $t);
        if ($t === '' || ! preg_match('/\d/', $t)) {
            return null;
        }
        $coma = strrpos($t, ',');
        $punto = strrpos($t, '.');
        if ($coma !== false && $punto !== false) {
            // El último separador es el decimal
            $t = $coma > $punto ? str_replace(',', '.', str_replace('.', '', $t)) : str_replace(',', '', $t);
        } elseif ($coma !== false) {
            $t = preg_match('/,\d{1,2}$/', $t) ? str_replace(',', '.', $t) : str_replace(',', '', $t);
        } elseif ($punto !== false && preg_match('/^\d{1,3}(\.\d{3})+$/', $t)) {
            $t = str_replace('.', '', $t);
        }

        return is_numeric($t) ? round((float) $t, 2) : null;
    }

    /** @param array<int, array<int, string>> $filas */
    public function importar(array $filas): array
    {
        [$filaTitulos, $mapa] = $this->buscarTitulos($filas);
        if ($mapa === null || ! isset($mapa['titulo'])) {
            $this->resumen['omitidos'][] = 'No se encontró la fila de títulos: el Excel debe tener una columna «Título» (y de preferencia «N°», «Regular» y «Link de pago»).';

            return $this->resumen;
        }
        $this->resumen['columnas'] = array_keys($mapa);

        foreach (array_slice($filas, $filaTitulos + 1, null, true) as $i => $fila) {
            $dato = fn (string $campo) => isset($mapa[$campo])
                ? trim(implode("\n", array_map(fn ($c) => trim((string) ($fila[$c] ?? '')), (array) $mapa[$campo])))
                : '';
            $titulo = preg_replace('/\s+/', ' ', $dato('titulo'));
            if ($titulo === '' || mb_strlen($titulo) < 4) {
                continue; // filas vacías o de notas
            }
            $numero = (int) preg_replace('/\D/', '', $dato('numero')) ?: null;
            $this->procesar($i + 1, $numero, $titulo, $dato);
        }

        return $this->resumen;
    }

    /** Qué campo es una columna según su título */
    private static function campoDe(string $n): ?string
    {
        foreach (self::COLUMNAS as $campo => $nombres) {
            if (in_array($n, $nombres, true)) {
                return $campo;
            }
        }
        if (str_contains($n, 'youtube')) {
            return 'video_url';
        }
        if (str_contains($n, 'descarga') || str_starts_with($n, 'videos') || str_starts_with($n, 'material')) {
            return 'enlaces_descarga';
        }
        if ((str_contains($n, 'link') || str_contains($n, 'pago')) && ! str_contains($n, 'video')) {
            return 'link_pago';
        }
        if (str_contains($n, 'regular') || str_starts_with($n, 'precio')) {
            return 'precio';
        }
        if (str_starts_with($n, 'ano') || str_contains($n, 'realizacion')) {
            return 'anio';
        }

        return null;
    }

    private function buscarTitulos(array $filas): array
    {
        foreach (array_slice($filas, 0, 15, true) as $i => $fila) {
            $mapa = [];
            foreach ($fila as $col => $texto) {
                $campo = self::campoDe(self::normalizar((string) $texto));
                if ($campo === 'enlaces_descarga') {
                    $mapa[$campo][] = $col;
                } elseif ($campo === 'link_pago') {
                    $mapa[$campo] = $col; // si hay varias (ej. «LINK DE PAGO» y «LINKS DE PAGO 2026») gana la última
                } elseif ($campo && ! isset($mapa[$campo])) {
                    $mapa[$campo] = $col;
                }
            }
            if (count($mapa) >= 2 || isset($mapa['titulo'])) {
                $mapa = $this->deducir($mapa, array_slice($filas, $i + 1, 30));
                if (isset($mapa['titulo'])) {
                    return [$i, $mapa];
                }
            }
        }

        return [0, null];
    }

    /** Columnas sin título: N° (números chicos), título (texto largo) y precio («$72,00») según su contenido */
    private function deducir(array $mapa, array $muestra): array
    {
        $usadas = collect($mapa)->flatten()->all();
        $max = collect($muestra)->map(fn ($f) => count($f))->max() ?? 0;
        $puntaje = [];
        for ($col = 0; $col < $max; $col++) {
            if (in_array($col, $usadas, true)) {
                continue;
            }
            $valores = collect($muestra)->map(fn ($f) => trim((string) ($f[$col] ?? '')))->filter()->values();
            if ($valores->isEmpty()) {
                continue;
            }
            $n = $valores->count();
            $puntaje[$col] = [
                'numero' => $valores->filter(fn ($v) => preg_match('/^#?\d{1,3}$/', $v))->count() / $n,
                'titulo' => $valores->filter(fn ($v) => mb_strlen($v) > 12 && ! preg_match('~^https?://~i', $v) && preg_match('/\pL{3}/u', $v))->count() / $n * min(1, $valores->avg(fn ($v) => mb_strlen($v)) / 25),
                'precio' => $valores->filter(fn ($v) => preg_match('/(\$|usd|us\$)\s*\d/i', $v))->count() / $n,
            ];
        }
        foreach (['numero', 'precio', 'titulo'] as $campo) {
            if (isset($mapa[$campo])) {
                continue;
            }
            $mejor = collect($puntaje)->map(fn ($p) => $p[$campo])->filter(fn ($v) => $v >= 0.6)->sortDesc()->keys()->first();
            if ($mejor !== null) {
                $mapa[$campo] = $mejor;
                unset($puntaje[$mejor]);
            }
        }

        return $mapa;
    }

    private function procesar(int $filaExcel, ?int $numero, string $titulo, callable $dato): void
    {
        $clave = self::normalizar($titulo);
        $curso = Curso::when($numero, fn ($q) => $q->where('numero', $numero))->get()
            ->first(fn (Curso $c) => self::normalizar($c->titulo) === $clave);
        if (! $curso && $numero) {
            $mismos = Curso::where('numero', $numero)->get();
            // Mismo N° y un solo curso con ese número: es ese (aunque el título tenga otra redacción)
            $curso = $mismos->count() === 1 ? $mismos->first() : $mismos->first(fn (Curso $c) => similar_text(self::normalizar($c->titulo), $clave) > mb_strlen($clave) * 0.7);
        }
        if (! $curso && ! $numero) {
            $curso = Curso::all()->first(fn (Curso $c) => self::normalizar($c->titulo) === $clave);
        }

        $cambios = array_filter([
            'titulo' => $titulo,
            'numero' => $numero,
            'anio' => ($a = (int) preg_replace('/\D/', '', $dato('anio'))) >= 1990 && $a <= 2100 ? $a : null,
            'precio' => self::precio($dato('precio')),
            'precio_oferta' => self::precio($dato('precio_oferta')),
            'incluye' => in_array(self::normalizar($dato('incluye')), ['', 'no', '-'], true) ? null : $dato('incluye'),
            'instructor' => self::nombrePropio($dato('instructor')) ?: null,
            'link_pago' => preg_match('~^https?://~i', $dato('link_pago')) ? $dato('link_pago') : null,
            'video_url' => preg_match('~youtu~i', $dato('video_url')) ? $dato('video_url') : null,
            'enlaces_descarga' => implode("\n", array_unique(array_merge(
                self::urls($dato('enlaces_descarga')),
                preg_match('~youtu~i', $dato('video_url')) ? [] : self::urls($dato('video_url')),
            ))) ?: null,
            'horas' => self::precio($dato('horas')),
            'idioma' => in_array(Str::lower($dato('idioma')), ['en', 'english', 'ingles', 'inglés'], true) ? 'en' : (in_array(Str::lower($dato('idioma')), ['es', 'español', 'espanol', 'spanish'], true) ? 'es' : null),
            'resumen' => $dato('resumen') ?: null,
            'dirigido_a' => $dato('dirigido_a') ?: null,
            'temario' => $dato('temario') ?: null,
            'instructor_bio' => $dato('instructor_bio') ?: null,
            'fecha_publicacion' => $this->fecha($dato('fecha_publicacion')),
        ], fn ($v) => $v !== null && $v !== '');
        if ($dato('publicado') !== '') {
            $cambios['publicado'] = ! in_array(self::normalizar($dato('publicado')), ['no', '0', 'falso', 'false', 'oculto'], true);
        }

        if ($curso) {
            unset($cambios['titulo']); // el título solo sirve para reconocerlo
            // «PABLO SOTO» e «Ing. Pablo Soto» son la misma persona: no se pisa el nombre con su título
            if (isset($cambios['instructor']) && self::persona($cambios['instructor']) === self::persona((string) $curso->instructor)) {
                unset($cambios['instructor']);
            }
            // Otro instructor: la trayectoria anterior ya no corresponde
            if (isset($cambios['instructor']) && ! isset($cambios['instructor_bio'])) {
                $cambios['instructor_bio'] = null;
            }
            $curso->fill($cambios);
            if ($curso->isDirty()) {
                $curso->save();
                $this->resumen['actualizados']++;
            } else {
                $this->resumen['sin_cambios']++;
            }

            return;
        }

        if (! $this->crearNuevos) {
            $this->resumen['omitidos'][] = "Fila {$filaExcel}: «" . Str::limit($titulo, 60) . '» no existe en el catálogo (no se creó).';

            return;
        }
        if (empty($cambios['precio'])) {
            $this->resumen['omitidos'][] = "Fila {$filaExcel}: «" . Str::limit($titulo, 60) . '» no tiene precio regular.';

            return;
        }
        Curso::create($cambios + ['publicado' => true, 'idioma' => 'es']);
        $this->resumen['creados']++;
    }

    private static function urls(string $t): array
    {
        preg_match_all('~https?://[^\s,;]+~i', $t, $m);

        return $m[0];
    }

    /** «CARLOS DE LA TORRE» → «Carlos De La Torre» (los nombres en mayúsculas se ven mejor así) */
    private static function nombrePropio(string $t): string
    {
        return $t !== '' && mb_strtoupper($t) === $t && preg_match('/\pL/u', $t) ? mb_convert_case(mb_strtolower($t), MB_CASE_TITLE) : $t;
    }

    private static function persona(string $nombre): string
    {
        return preg_replace('/^(ing|dr|dra|msc|mg|lic|ingeniero|doctor)\.?\s+/', '', self::normalizar($nombre));
    }

    /** «15/05/2025», «2025-05-15» o el número de serie de Excel (45792) */
    private function fecha(string $t): ?string
    {
        if ($t === '') {
            return null;
        }
        if (is_numeric($t) && (float) $t > 30000) {
            return date('Y-m-d', (int) round(((float) $t - 25569) * 86400));
        }
        if (preg_match('~^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$~', $t, $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]) : null;
        }

        return strtotime($t) ? date('Y-m-d', strtotime($t)) : null;
    }
}
