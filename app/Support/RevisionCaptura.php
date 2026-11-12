<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Revisa la captura del pago que sube el cliente (Yape, Plin o transferencia).
 *
 * Ojo: ninguna captura PRUEBA un pago (se puede editar). Esto sirve para frenar lo obvio
 * —archivos que no son imágenes, capturas recicladas, imágenes editadas— y darle pistas a recepción.
 * La confirmación final siempre es ver el dinero en la app de Yape / Plin o en la banca.
 */
class RevisionCaptura
{
    /** Programas de edición que dejan su nombre dentro del archivo */
    private const EDITORES = [
        'photoshop' => 'Photoshop', 'imageready' => 'Photoshop', 'gimp' => 'GIMP', 'picsart' => 'PicsArt',
        'snapseed' => 'Snapseed', 'canva' => 'Canva', 'lightroom' => 'Lightroom', 'pixlr' => 'Pixlr',
        'photopea' => 'Photopea', 'paint.net' => 'Paint.NET', 'affinity' => 'Affinity', 'illustrator' => 'Illustrator',
        'corel' => 'Corel', 'inkscape' => 'Inkscape', 'fotor' => 'Fotor', 'polarr' => 'Polarr', 'meitu' => 'Meitu',
    ];

    /** Solo letras y números en mayúscula: «N° 0012-3456» y «00123456» son la misma operación */
    public static function normalizarOperacion(?string $operacion): string
    {
        return mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $operacion));
    }

    /**
     * Devuelve ['error' => mensaje para el cliente] si el archivo no sirve,
     * o ['hash' => …, 'alertas' => [...], 'info' => [...]] si se puede guardar.
     */
    public static function revisar(UploadedFile $archivo): array
    {
        $ruta = $archivo->getRealPath();
        $contenido = (string) @file_get_contents($ruta);
        if ($contenido === '') {
            return ['error' => 'No pudimos leer el archivo. Vuelve a subir la captura.'];
        }

        $hash = hash('sha256', $contenido);
        $alertas = [];
        $info = [];

        if (str_starts_with($contenido, '%PDF')) {
            // Constancia en PDF (transferencias desde la banca por internet)
            $info['tipo'] = 'pdf';
            $info['paginas'] = max(1, preg_match_all('/\/Type\s*\/Page[^s]/', $contenido));
        } else {
            $tam = @getimagesizefromstring($contenido);
            if (! $tam || ! in_array($tam[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                return ['error' => 'El archivo no es una imagen válida. Sube la captura de pantalla de tu pago (JPG o PNG).'];
            }
            [$ancho, $alto] = $tam;
            // Una captura de celular se lee bien desde unos 300 px de ancho
            if ($ancho < 300 || $alto < 300) {
                return ['error' => 'La imagen es muy pequeña para leerla. Sube la captura completa de tu pago, sin recortar.'];
            }
            // Que de verdad se pueda abrir (no un archivo dañado o disfrazado)
            if (function_exists('imagecreatefromstring') && $ancho * $alto <= 40_000_000) {
                $img = @imagecreatefromstring($contenido);
                if ($img === false) {
                    return ['error' => 'La imagen está dañada o no se puede abrir. Toma otra captura y súbela.'];
                }
                imagedestroy($img);
            }
            $info += ['tipo' => 'imagen', 'ancho' => $ancho, 'alto' => $alto];

            if ($ancho > $alto * 1.15) {
                $alertas[] = 'La imagen es horizontal: no parece una captura de celular (puede ser de una computadora o una foto).';
            }
        }

        // ¿Pasó por un programa de edición? (lo dejan escrito en los metadatos)
        $cabecera = mb_strtolower(substr($contenido, 0, 200_000) . substr($contenido, -50_000));
        foreach (self::EDITORES as $marca => $nombre) {
            if (str_contains($cabecera, $marca)) {
                $alertas[] = "El archivo fue guardado con {$nombre} (programa de edición de imágenes). Revisa con más cuidado.";
                break;
            }
        }

        // Fotos de cámara (no capturas) traen datos de la cámara: posible foto de otra pantalla
        if (function_exists('exif_read_data') && ($info['tipo'] ?? '') === 'imagen' && str_starts_with($contenido, "\xFF\xD8")) {
            $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($contenido));
            if (is_array($exif) && (! empty($exif['Make']) || ! empty($exif['FNumber']))) {
                $alertas[] = 'Es una foto tomada con cámara' . (! empty($exif['Model']) ? ' (' . trim($exif['Model']) . ')' : '') . ', no una captura de pantalla.';
            }
        }

        return ['hash' => $hash, 'huella' => self::huella($contenido), 'alertas' => $alertas, 'info' => $info];
    }

    /**
     * Huella visual (256 bits en hexadecimal): la imagen reducida a 17×16 en grises,
     * comparando cada punto con el de al lado. Si la vuelven a guardar, la comprimen
     * o le cambian el nombre, la huella casi no cambia.
     */
    public static function huella(string $contenido): ?string
    {
        if (! function_exists('imagecreatefromstring') || str_starts_with($contenido, '%PDF')) {
            return null;
        }
        $img = @imagecreatefromstring($contenido);
        if ($img === false) {
            return null;
        }
        $mini = imagecreatetruecolor(17, 16);
        imagecopyresampled($mini, $img, 0, 0, 0, 0, 17, 16, imagesx($img), imagesy($img));
        imagedestroy($img);
        $bits = '';
        for ($y = 0; $y < 16; $y++) {
            $antes = null;
            for ($x = 0; $x < 17; $x++) {
                $rgb = imagecolorat($mini, $x, $y);
                $luz = (($rgb >> 16) & 255) * 0.3 + (($rgb >> 8) & 255) * 0.59 + ($rgb & 255) * 0.11;
                if ($antes !== null) {
                    $bits .= $luz > $antes ? '1' : '0';
                }
                $antes = $luz;
            }
        }
        imagedestroy($mini);

        return implode('', array_map(fn ($b) => dechex(bindec($b)), str_split($bits, 4)));
    }

    /** Cuántos de los 256 puntos son distintos entre dos huellas (0 = la misma imagen) */
    public static function distancia(string $a, string $b): int
    {
        $d = 0;
        for ($i = 0, $n = min(strlen($a), strlen($b)); $i < $n; $i++) {
            $d += substr_count(decbin(hexdec($a[$i]) ^ hexdec($b[$i])), '1');
        }

        return $d;
    }
}
