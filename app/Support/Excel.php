<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Lee y escribe hojas de cálculo sencillas sin librerías externas:
 *  - leer(): .xlsx (Excel) o .csv → filas (arreglos de textos). Si una celda tiene un
 *    hipervínculo (por ejemplo el texto «Comprar» con el link de Niubiz), devuelve el link.
 *  - escribir(): filas → archivo .xlsx con la primera fila en negrita.
 */
class Excel
{
    /** @return array<int, array<int, string>> */
    public static function leer(string $ruta, string $extension): array
    {
        return in_array(strtolower($extension), ['csv', 'txt', 'tsv'], true) ? self::leerCsv($ruta) : self::leerXlsx($ruta);
    }

    private static function leerCsv(string $ruta): array
    {
        $texto = (string) file_get_contents($ruta);
        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);
        if (! mb_check_encoding($texto, 'UTF-8')) {
            $texto = mb_convert_encoding($texto, 'UTF-8', 'Windows-1252');
        }
        $primera = strtok($texto, "\n");
        $sep = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : (substr_count($primera, "\t") > substr_count($primera, ',') ? "\t" : ',');
        $f = fopen('php://memory', 'r+');
        fwrite($f, $texto);
        rewind($f);
        $filas = [];
        while (($fila = fgetcsv($f, 0, $sep, '"', '')) !== false) {
            $filas[] = array_map(fn ($v) => trim((string) $v), $fila);
        }
        fclose($f);

        return $filas;
    }

    private static function leerXlsx(string $ruta): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Falta la extensión «zip» de PHP. Actívela en php.ini o guarde el Excel como CSV.');
        }
        $zip = new ZipArchive();
        if ($zip->open($ruta) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo. ¿Es un Excel (.xlsx)? Los .xls antiguos guárdelos como .xlsx o .csv.');
        }

        // Textos compartidos
        $compartidos = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $ss = self::xml($xml);
            foreach ($ss->si as $si) {
                $compartidos[] = self::textoDe($si);
            }
        }

        // Primera hoja (según el libro)
        $hoja = 'xl/worksheets/sheet1.xml';
        $wb = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wb !== false && $rels !== false) {
            $wbx = self::xml($wb);
            $primera = $wbx->sheets->sheet[0] ?? null;
            $rid = $primera ? (string) $primera->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] : null;
            foreach (self::xml($rels)->Relationship as $r) {
                if ((string) $r['Id'] === $rid) {
                    $destino = ltrim((string) $r['Target'], '/');
                    $hoja = str_starts_with($destino, 'xl/') ? $destino : 'xl/' . $destino;
                }
            }
        }
        $xmlHoja = $zip->getFromName($hoja);
        if ($xmlHoja === false) {
            throw new RuntimeException('El Excel no tiene hojas con datos.');
        }

        // Hipervínculos de la hoja: celda → URL
        $links = [];
        $relsHoja = $zip->getFromName(preg_replace('~worksheets/(sheet\d+\.xml)$~', 'worksheets/_rels/$1.rels', $hoja));
        $destinos = [];
        if ($relsHoja !== false) {
            foreach (self::xml($relsHoja)->Relationship as $r) {
                $destinos[(string) $r['Id']] = (string) $r['Target'];
            }
        }
        $sx = self::xml($xmlHoja);
        if (isset($sx->hyperlinks)) {
            foreach ($sx->hyperlinks->hyperlink as $h) {
                $id = (string) $h->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                if (isset($destinos[$id])) {
                    foreach (self::rango((string) $h['ref']) as $celda) {
                        $links[$celda] = $destinos[$id];
                    }
                }
            }
        }
        $zip->close();

        $filas = [];
        foreach ($sx->sheetData->row as $row) {
            $fila = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $col = self::columna($ref);
                $tipo = (string) $c['t'];
                $v = match ($tipo) {
                    's' => $compartidos[(int) $c->v] ?? '',
                    'inlineStr' => self::textoDe($c->is),
                    'b' => ((string) $c->v) === '1' ? 'Sí' : 'No',
                    default => (string) $c->v,
                };
                if (isset($links[$ref]) && preg_match('~^https?://~i', $links[$ref])) {
                    $v = $links[$ref];
                }
                $fila[$col] = trim($v);
            }
            if ($fila) {
                $max = max(array_keys($fila));
                $filas[(int) $row['r'] - 1] = array_replace(array_fill(0, $max + 1, ''), $fila);
            }
        }
        ksort($filas);

        return array_values($filas);
    }

    /** Filas → contenido binario de un .xlsx */
    public static function escribir(array $filas, array $anchos = []): string
    {
        $esc = fn ($t) => htmlspecialchars((string) $t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xmlFilas = '';
        foreach (array_values($filas) as $i => $fila) {
            $xmlFilas .= '<row r="' . ($i + 1) . '">';
            foreach (array_values($fila) as $j => $v) {
                $ref = self::letra($j) . ($i + 1);
                $estilo = $i === 0 ? ' s="1"' : '';
                if (is_int($v) || is_float($v)) {
                    $xmlFilas .= "<c r=\"{$ref}\"{$estilo}><v>{$v}</v></c>";
                } elseif ($v !== null && $v !== '') {
                    $xmlFilas .= "<c r=\"{$ref}\" t=\"inlineStr\"{$estilo}><is><t xml:space=\"preserve\">{$esc($v)}</t></is></c>";
                }
            }
            $xmlFilas .= '</row>';
        }
        $cols = '';
        foreach ($anchos as $j => $w) {
            $cols .= '<col min="' . ($j + 1) . '" max="' . ($j + 1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $hoja = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . ($cols ? "<cols>{$cols}</cols>" : '') . "<sheetData>{$xmlFilas}</sheetData></worksheet>";

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Cursos" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0B2A5B"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $hoja);
        $zip->close();
        $contenido = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $contenido;
    }

    private static function xml(string $xml): SimpleXMLElement
    {
        $x = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        if ($x === false) {
            throw new RuntimeException('El archivo Excel está dañado.');
        }

        return $x;
    }

    /** Texto de <si> o <is>, incluido el texto con formato (<r><t>) */
    private static function textoDe(?SimpleXMLElement $n): string
    {
        if (! $n) {
            return '';
        }
        if (isset($n->t)) {
            return (string) $n->t;
        }
        $t = '';
        foreach ($n->r as $r) {
            $t .= (string) $r->t;
        }

        return $t;
    }

    private static function columna(string $ref): int
    {
        $letras = preg_replace('/\d/', '', $ref);
        $n = 0;
        foreach (str_split($letras) as $l) {
            $n = $n * 26 + (ord($l) - 64);
        }

        return $n - 1;
    }

    private static function letra(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }

        return $s;
    }

    /** «A1:B3» → [A1, A2, A3, B1, …] (para hipervínculos que abarcan varias celdas) */
    private static function rango(string $ref): array
    {
        if (! str_contains($ref, ':')) {
            return [$ref];
        }
        [$a, $b] = explode(':', $ref);
        $c1 = self::columna($a);
        $c2 = self::columna($b);
        $f1 = (int) preg_replace('/\D/', '', $a);
        $f2 = (int) preg_replace('/\D/', '', $b);
        $celdas = [];
        for ($c = $c1; $c <= $c2 && $c - $c1 < 50; $c++) {
            for ($f = $f1; $f <= $f2 && $f - $f1 < 2000; $f++) {
                $celdas[] = self::letra($c) . $f;
            }
        }

        return $celdas;
    }
}
