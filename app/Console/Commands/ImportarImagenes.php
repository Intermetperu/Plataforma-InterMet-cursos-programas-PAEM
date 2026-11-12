<?php

namespace App\Console\Commands;

use App\Support\ImportadorImagenes;
use Illuminate\Console\Command;
use Throwable;

/** php artisan cursos:importar-imagenes [archivo.zip] [--reemplazar] */
class ImportarImagenes extends Command
{
    protected $signature = 'cursos:importar-imagenes
        {zip=Imagenes-cursos-InterMet.zip : ZIP con las imágenes (por defecto, el que está en la carpeta de la plataforma)}
        {--reemplazar : Cambiar también las imágenes que los cursos ya tienen}';

    protected $description = 'Pone a cada curso su imagen desde un ZIP (archivos «N°-idioma-….jpg»)';

    public function handle(): int
    {
        $zip = (string) $this->argument('zip');
        if (! is_file($zip) && is_file(base_path($zip))) {
            $zip = base_path($zip);
        }
        if (! is_file($zip)) {
            $this->error("  No encuentro el archivo {$zip}. Póngalo en la carpeta «plataforma» o indique la ruta completa.");

            return self::FAILURE;
        }

        try {
            $r = ImportadorImagenes::importar($zip, (bool) $this->option('reemplazar'));
        } catch (Throwable $e) {
            $this->error('  ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->line("  <fg=green;options=bold>LISTO</>  Imágenes puestas en los cursos: <options=bold>{$r['asignadas']}</>");
        if ($r['ya_tenian']) {
            $this->line("  Cursos que ya tenían imagen (no se tocaron): {$r['ya_tenian']}  ·  para cambiarlas use --reemplazar");
        }
        foreach (array_slice($r['omitidas'], 0, 30) as $o) {
            $this->line("  <fg=yellow>•</> {$o}");
        }
        $this->newLine();

        return self::SUCCESS;
    }
}
