<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\PedidoCurso;
use App\Support\Excel;
use App\Support\ImportadorCursos;
use App\Support\ImportadorImagenes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Catálogo: agregar, editar y publicar cursos; importar y exportar en Excel. */
class CursoController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $filtro = $request->query('filtro');

        $cursos = Curso::query()
            ->when($q !== '', fn ($c) => $c->where(fn ($w) => $w->where('titulo', 'like', "%$q%")->orWhere('instructor', 'like', "%$q%")
                ->when(ctype_digit($q), fn ($w) => $w->orWhere('numero', (int) $q))))
            ->when($request->query('anio'), fn ($c, $a) => $c->where('anio', $a))
            ->when($filtro === 'con_link', fn ($c) => $c->whereNotNull('link_pago')->where('link_pago', '!=', ''))
            ->when($filtro === 'sin_link', fn ($c) => $c->where(fn ($w) => $w->whereNull('link_pago')->orWhere('link_pago', '')))
            ->when($filtro === 'sin_video', fn ($c) => $c->whereNull('video_url'))
            ->when($filtro === 'sin_temario', fn ($c) => $c->whereNull('temario'))
            ->when($filtro === 'ocultos', fn ($c) => $c->where('publicado', false))
            ->when($filtro === 'oferta', fn ($c) => $c->where('en_oferta', true))
            ->orderByDesc('numero')->orderBy('idioma')
            ->paginate(25)->withQueryString();

        return view('admin.cursos.index', [
            'cursos' => $cursos,
            'q' => $q,
            'filtro' => $filtro,
            'anios' => Curso::whereNotNull('anio')->distinct()->orderByDesc('anio')->pluck('anio'),
            'conteo' => [
                'todos' => Curso::count(),
                'con_link' => Curso::whereNotNull('link_pago')->where('link_pago', '!=', '')->count(),
                'sin_link' => Curso::where(fn ($w) => $w->whereNull('link_pago')->orWhere('link_pago', ''))->count(),
                'sin_video' => Curso::whereNull('video_url')->count(),
                'sin_temario' => Curso::whereNull('temario')->count(),
                'ocultos' => Curso::where('publicado', false)->count(),
                'oferta' => Curso::where('en_oferta', true)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.cursos.form', ['curso' => new Curso(['publicado' => true, 'idioma' => 'es', 'anio' => now()->year, 'numero' => (Curso::max('numero') ?? 0) + 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $curso = new Curso();
        $this->guardar($request, $curso);

        return redirect()->route('admin.cursos.edit', $curso)->with('ok', "Curso {$curso->numero_texto} creado. Así se ve en la web: " . $curso->url);
    }

    public function edit(Curso $curso): View
    {
        return view('admin.cursos.form', ['curso' => $curso, 'ventas' => PedidoCurso::where('curso_id', $curso->id)->count()]);
    }

    public function update(Request $request, Curso $curso): RedirectResponse
    {
        $this->guardar($request, $curso);

        return back()->with('ok', 'Curso guardado.');
    }

    public function destroy(Curso $curso): RedirectResponse
    {
        // Si ya se vendió, se oculta (no se borra) para no perder los pedidos
        if (PedidoCurso::where('curso_id', $curso->id)->exists()) {
            $curso->update(['publicado' => false]);

            return redirect()->route('admin.cursos.index')->with('ok', "El curso {$curso->numero_texto} tiene ventas: se ocultó de la web en lugar de eliminarlo.");
        }
        $this->borrarImagen($curso);
        $curso->delete();

        return redirect()->route('admin.cursos.index')->with('ok', 'Curso eliminado.');
    }

    public function publicar(Curso $curso): RedirectResponse
    {
        $curso->update(['publicado' => ! $curso->publicado]);

        return back()->with('ok', "{$curso->numero_texto} " . ($curso->publicado ? 'ya se ve en la web.' : 'quedó oculto de la web.'));
    }

    private function guardar(Request $request, Curso $curso): void
    {
        $datos = $request->validate([
            'numero' => ['nullable', 'integer', 'min:1', 'max:65000'],
            'titulo' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9-]+$/', Rule::unique('cursos', 'slug')->ignore($curso->id)],
            'idioma' => ['required', Rule::in(array_keys(Curso::IDIOMAS))],
            'anio' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'fecha_publicacion' => ['nullable', 'date'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999'],
            'precio_oferta' => ['nullable', 'numeric', 'min:0', 'lt:precio'],
            'horas' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'incluye' => ['nullable', 'string', 'max:120'],
            'instructor' => ['nullable', 'string', 'max:255'],
            'instructor_bio' => ['nullable', 'string', 'max:5000'],
            'resumen' => ['nullable', 'string', 'max:1000'],
            'dirigido_a' => ['nullable', 'string', 'max:3000'],
            'temario' => ['nullable', 'string', 'max:15000'],
            'video_url' => ['nullable', 'string', 'max:255'],
            'link_pago' => ['nullable', 'url', 'max:255'],
            'enlaces_descarga' => ['nullable', 'string', 'max:3000'],
            'imagen_archivo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'precio_oferta.lt' => 'El precio de oferta debe ser menor que el precio regular.',
            'slug.regex' => 'La dirección solo puede tener letras minúsculas, números y guiones.',
        ], [
            'titulo' => 'título', 'precio' => 'precio regular', 'precio_oferta' => 'precio de oferta', 'anio' => 'año',
            'link_pago' => 'link de pago', 'video_url' => 'video', 'imagen_archivo' => 'imagen', 'slug' => 'dirección web',
        ]);

        if (filled($datos['video_url'] ?? null)) {
            $prueba = new Curso(['video_url' => $datos['video_url']]);
            if (! $prueba->youtube_id) {
                back()->withInput()->withErrors(['video_url' => 'Pegue el enlace de YouTube completo (ej.: https://youtu.be/DemzVmD19_Q).'])->throwResponse();
            }
        }

        $curso->fill(collect($datos)->except('imagen_archivo')->all());
        $curso->en_oferta = $request->boolean('en_oferta') && filled($datos['precio_oferta'] ?? null);
        $curso->publicado = $request->boolean('publicado');
        $curso->destacado = $request->boolean('destacado');
        if (blank($datos['slug'] ?? null) && ! $curso->exists) {
            $curso->slug = null;
        } elseif (blank($datos['slug'] ?? null)) {
            $curso->slug = $curso->getOriginal('slug');
        }

        if ($request->boolean('quitar_imagen')) {
            $this->borrarImagen($curso);
            $curso->imagen = null;
        }
        if ($request->hasFile('imagen_archivo')) {
            $this->borrarImagen($curso);
            $nombre = 'curso-' . uniqid() . '.' . $request->file('imagen_archivo')->extension();
            $request->file('imagen_archivo')->move(public_path('uploads/cursos'), $nombre);
            $curso->imagen = 'uploads/cursos/' . $nombre;
        }
        $curso->save();
    }

    private function borrarImagen(Curso $curso): void
    {
        if ($curso->imagen && str_starts_with($curso->imagen, 'uploads/')) {
            File::delete(public_path($curso->imagen));
        }
    }

    // ---------------- Excel ----------------
    public function importarForm(): View
    {
        return view('admin.cursos.importar', ['resumen' => session('resumen'), 'resumenImagenes' => session('resumen_imagenes')]);
    }

    /** ZIP con una imagen por curso: «N°-idioma-….jpg» (ej.: 003-es-dimensionamiento.jpg) */
    public function importarImagenes(Request $request): RedirectResponse
    {
        $request->validate([
            'zip' => ['required', 'file', 'mimes:zip', 'max:102400'],
        ], [
            'zip.mimes' => 'Suba el archivo .zip con las imágenes.',
            'zip.uploaded' => 'El ZIP pesa más de lo que su PHP permite subir (' . ini_get('upload_max_filesize') . '). Use el archivo IMPORTAR-IMAGENES.bat de la carpeta de la plataforma.',
        ], ['zip' => 'ZIP']);

        try {
            $r = ImportadorImagenes::importar($request->file('zip')->getRealPath(), $request->boolean('reemplazar'));
        } catch (Throwable $e) {
            return back()->withErrors(['zip' => $e->getMessage()]);
        }

        return redirect()->route('admin.cursos.importar')
            ->with('resumen_imagenes', $r)
            ->with('ok', "Listo: {$r['asignadas']} cursos ya tienen su imagen.");
    }

    public function importar(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'max:10240', 'mimes:xlsx,csv,txt,tsv'],
        ], ['archivo.mimes' => 'Suba un Excel (.xlsx), CSV o TXT. Si es .xls, ábralo y guárdelo como .xlsx.'], ['archivo' => 'archivo']);

        $archivo = $request->file('archivo');
        try {
            $filas = Excel::leer($archivo->getRealPath(), $archivo->getClientOriginalExtension());
        } catch (Throwable $e) {
            return back()->withErrors(['archivo' => $e->getMessage()]);
        }

        return $this->terminarImportacion($filas, $request->boolean('crear'));
    }

    /**
     * Importa directo desde una hoja de Google Sheets compartida («Cualquier persona con el enlace»).
     * Se descarga la hoja como CSV: no hace falta bajar el Excel.
     */
    public function importarHoja(Request $request): RedirectResponse
    {
        $request->validate(['hoja' => ['required', 'url', 'max:500']], [], ['hoja' => 'link de la hoja']);
        $url = (string) $request->input('hoja');
        if (! preg_match('~docs\.google\.com/spreadsheets/d/([\w-]{20,})~', $url, $m)) {
            return back()->withInput()->withErrors(['hoja' => 'Pegue el link de una hoja de Google Sheets (docs.google.com/spreadsheets/…).']);
        }
        $gid = preg_match('~[#?&]gid=(\d+)~', $url, $g) ? '&gid=' . $g[1] : '';
        $csv = "https://docs.google.com/spreadsheets/d/{$m[1]}/export?format=csv{$gid}";

        try {
            $resp = Http::timeout(25)->withOptions(['allow_redirects' => true])->get($csv);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['hoja' => 'No se pudo conectar con Google Sheets. Revise la conexión a internet del servidor e intente otra vez.']);
        }
        if (! $resp->successful() || str_contains(strtolower($resp->header('Content-Type')), 'text/html')) {
            return back()->withInput()->withErrors(['hoja' => 'Google no dejó leer la hoja. En la hoja: Compartir › Acceso general › «Cualquier persona con el enlace» (Lector) y vuelva a intentar.']);
        }

        \App\Models\Ajuste::guardar('hoja_cursos', $url);
        $tmp = tempnam(sys_get_temp_dir(), 'hoja') . '.csv';
        file_put_contents($tmp, $resp->body());
        try {
            $filas = Excel::leer($tmp, 'csv');
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['hoja' => $e->getMessage()]);
        } finally {
            @unlink($tmp);
        }

        return $this->terminarImportacion($filas, $request->boolean('crear'));
    }

    private function terminarImportacion(array $filas, bool $crear): RedirectResponse
    {
        $resumen = (new ImportadorCursos($crear))->importar($filas);

        return redirect()->route('admin.cursos.importar')->with('resumen', $resumen)
            ->with('ok', "Importación terminada: {$resumen['actualizados']} actualizados, {$resumen['creados']} nuevos, {$resumen['sin_cambios']} sin cambios.");
    }

    /** Todo el catálogo en Excel: para completar los links de pago, videos, horas… y volver a importarlo */
    public function plantilla(): Response
    {
        $filas = [['N°', 'Título', 'Idioma', 'Año', 'Oferta', 'Regular', 'Incluye software', 'Instructor', 'Horas', 'Link de pago', 'Enlaces de descarga', 'Video YouTube', 'Publicado']];
        foreach (Curso::orderBy('numero')->orderBy('idioma')->get() as $c) {
            $filas[] = [
                $c->numero, $c->titulo, $c->idioma, $c->anio, $c->precio_oferta !== null ? (float) $c->precio_oferta : '', (float) $c->precio,
                $c->incluye ?? 'No', $c->instructor, $c->horas !== null ? (float) $c->horas : '', $c->link_pago, str_replace("\n", ' ', (string) $c->enlaces_descarga), $c->video_url, $c->publicado ? 'Sí' : 'No',
            ];
        }

        return response(Excel::escribir($filas, [6, 70, 8, 7, 9, 9, 18, 34, 7, 58, 40, 34, 10]), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="cursos-intermet-' . now()->format('Y-m-d') . '.xlsx"',
        ]);
    }
}
