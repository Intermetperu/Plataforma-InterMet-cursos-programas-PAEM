<?php

namespace App\Support;

use App\Models\Curso;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Pone a cada curso su imagen desde un ZIP.
 * Cada archivo se llama «N°-idioma-lo-que-sea.jpg» (ej.: 003-es-dimensionamiento.jpg = curso N° 3 en español).
 * Las imágenes se guardan en public/uploads/cursos (reducidas a 1280 px si son más grandes).
 */
class ImportadorImagenes
{
    private const TIPOS = ['jpg', 'jpeg', 'png', 'webp'];

    /** @return array{asignadas: int, ya_tenian: int, omitidas: array<string>} */
    public static function importar(string $zip, bool $reemplazar = false): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Su PHP no tiene activada la extensión «zip». En Laragon: Menú › PHP › Extensiones › zip.');
        }
        $z = new ZipArchive;
        if ($z->open($zip) !== true) {
            throw new RuntimeException('No se pudo abrir el ZIP. ¿Está completo?');
        }

        File::ensureDirectoryExists(public_path('uploads/cursos'));
        $cursos = Curso::all()->keyBy(fn (Curso $c) => (int) $c->numero . '-' . $c->idioma);
        $r = ['asignadas' => 0, 'ya_tenian' => 0, 'omitidas' => []];

        for ($i = 0; $i < $z->numFiles; $i++) {
            $ruta = str_replace('\\', '/', (string) $z->getNameIndex($i));
            $nombre = basename($ruta);
            $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            if (str_ends_with($ruta, '/') || str_contains($ruta, '__MACOSX') || str_starts_with($nombre, '.') || ! in_array($ext, self::TIPOS, true)) {
                continue;
            }
            if (stripos($ruta, 'youtube') !== false || stripos($ruta, 'no se importan') !== false || stripos($ruta, 'no-importar') !== false) {
                continue;
            }
            if (! preg_match('/^(\d{1,3})-(es|en)-/i', $nombre, $m)) {
                $r['omitidas'][] = "{$nombre}: el nombre debe empezar con el N° y el idioma (ej.: 003-es-…)";

                continue;
            }
            $curso = $cursos->get((int) $m[1] . '-' . strtolower($m[2]));
            if (! $curso) {
                $r['omitidas'][] = "{$nombre}: no hay un curso N° " . (int) $m[1] . ' en ' . (strtolower($m[2]) === 'en' ? 'inglés' : 'español');

                continue;
            }
            if ($curso->imagen && ! $reemplazar) {
                $r['ya_tenian']++;

                continue;
            }
            $contenido = $z->getFromIndex($i);
            if ($contenido === false || ! @getimagesizefromstring($contenido)) {
                $r['omitidas'][] = "{$nombre}: no es una imagen válida";

                continue;
            }

            if ($curso->imagen && str_starts_with($curso->imagen, 'uploads/')) {
                File::delete(public_path($curso->imagen));
            }
            $curso->imagen = self::guardar($contenido, $ext, $curso);
            $curso->save();
            $r['asignadas']++;
        }
        $z->close();

        return $r;
    }

    private static function guardar(string $contenido, string $ext, Curso $curso): string
    {
        $base = 'uploads/cursos/' . Str::limit(Str::slug(($curso->numero ? $curso->numero . '-' : '') . $curso->titulo), 70, '') . '-' . $curso->id . '-' . substr(md5($contenido), 0, 6);
        $info = @getimagesizefromstring($contenido);

        if ($info && $info[0] > 1280 && function_exists('imagecreatefromstring') && ($img = @imagecreatefromstring($contenido))) {
            $alto = (int) round($info[1] * 1280 / $info[0]);
            $nueva = imagecreatetruecolor(1280, $alto);
            imagefill($nueva, 0, 0, imagecolorallocate($nueva, 255, 255, 255));
            imagecopyresampled($nueva, $img, 0, 0, 0, 0, 1280, $alto, $info[0], $info[1]);
            imagejpeg($nueva, public_path($base . '.jpg'), 85);

            return $base . '.jpg';
        }
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        File::put(public_path($base . '.' . $ext), $contenido);

        return $base . '.' . $ext;
    }
}
