<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Programa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Programas de alta especialización (PAEM): precio, link de pago y cursos incluidos */
class ProgramaController extends Controller
{
    public function index(Request $request): View
    {
        $todos = Programa::orderBy('orden')->orderBy('numero')->with('cursos:id,numero,precio')->get();
        $conLink = fn ($p) => filled($p->link_pago);
        $filtro = (string) $request->query('filtro', '');
        $q = trim((string) $request->query('q', ''));
        $programas = $todos
            ->when($filtro === 'con_link', fn ($c) => $c->filter($conLink))
            ->when($filtro === 'sin_link', fn ($c) => $c->reject($conLink))
            ->when($filtro === 'ocultos', fn ($c) => $c->where('publicado', false))
            ->when($q !== '', fn ($c) => $c->filter(fn ($p) => Str::contains(Str::lower(Str::ascii($p->titulo . ' ' . $p->numero)), Str::lower(Str::ascii($q)))))
            ->values();

        return view('admin.programas.index', [
            'programas' => $programas,
            'filtro' => $filtro,
            'q' => $q,
            'conteo' => [
                '' => $todos->count(),
                'con_link' => $todos->filter($conLink)->count(),
                'sin_link' => $todos->reject($conLink)->count(),
                'ocultos' => $todos->where('publicado', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.programas.form', ['programa' => new Programa(['color' => 'azul', 'icono' => 'bx-award', 'publicado' => true]), 'numerosCursos' => '']);
    }

    public function edit(Programa $programa): View
    {
        $programa->load('cursos');

        return view('admin.programas.form', ['programa' => $programa, 'numerosCursos' => $programa->cursos->pluck('numero')->filter()->implode(', ')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $programa = new Programa;
        $this->guardar($request, $programa);

        return redirect()->route('admin.programas.index')->with('ok', 'Programa creado.');
    }

    public function update(Request $request, Programa $programa): RedirectResponse
    {
        $this->guardar($request, $programa);

        return redirect()->route('admin.programas.index')->with('ok', 'Programa «' . $programa->titulo . '» guardado.');
    }

    public function publicar(Programa $programa): RedirectResponse
    {
        $programa->update(['publicado' => ! $programa->publicado]);

        return back()->with('ok', $programa->publicado ? 'El programa ya se ve en la web.' : 'El programa quedó oculto.');
    }

    private function guardar(Request $request, Programa $programa): void
    {
        $datos = $request->validate([
            'numero' => ['nullable', 'integer', 'min:1', 'max:999'],
            'titulo' => ['required', 'string', 'max:190'],
            'nombre' => ['required', 'string', 'max:60'],
            'subtitulo' => ['nullable', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'precio' => ['required', 'numeric', 'min:1', 'max:99999'],
            'link_pago' => ['nullable', 'url', 'max:255'],
            'color' => ['required', Rule::in(array_keys(Programa::COLORES))],
            'area' => ['nullable', Rule::in(array_keys(Programa::AREAS))],
            'icono' => ['required', 'string', 'max:40', 'regex:/^bxs?-[a-z0-9-]+$/'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:999'],
            'cursos' => ['required', 'string', 'max:500'],
            'temario' => ['nullable', 'string', 'max:20000'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'miniaturas' => ['nullable', 'array', 'max:20'],
            'miniaturas.*' => ['image', 'max:4096'],
            'quitar_miniaturas' => ['nullable', 'array'],
            'orden_miniaturas' => ['nullable', 'array'],
            'quitar_imagen' => ['nullable', 'boolean'],
        ], [
            'miniaturas.*.image' => 'Las miniaturas deben ser imágenes (JPG, PNG o WEBP).',
            'miniaturas.*.max' => 'Cada miniatura debe pesar menos de 4 MB.',
            'cursos.required' => 'Escriba los N° de los cursos que incluye el programa (ej.: 1, 6, 15).',
            'icono.regex' => 'El ícono debe ser un nombre de Boxicons, por ejemplo bx-water.',
        ]);

        // N° de cursos: «1, 6, 15» → cursos (la versión en español si hay dos)
        $numeros = collect(preg_split('/[^\d]+/', $datos['cursos']))->filter()->map(fn ($n) => (int) $n)->unique()->values();
        $porNumero = Curso::whereIn('numero', $numeros)->orderByRaw("idioma = 'es' desc")->get()->unique('numero')->keyBy('numero');
        $faltan = $numeros->reject(fn ($n) => $porNumero->has($n));
        if ($faltan->isNotEmpty()) {
            throw ValidationException::withMessages(['cursos' => 'No existen los cursos N° ' . $faltan->implode(', ') . '.']);
        }

        $programa->fill(collect($datos)->except(['cursos', 'imagen', 'quitar_imagen', 'miniaturas', 'quitar_miniaturas', 'orden_miniaturas'])->all());
        $programa->fotos_cursos = $request->boolean('fotos_cursos');
        $programa->orden = $datos['orden'] ?? ($programa->orden ?: (int) Programa::max('orden') + 1);
        $programa->publicado = $request->boolean('publicado');

        if ($request->boolean('quitar_imagen') && $programa->imagen) {
            File::delete(public_path($programa->imagen));
            $programa->imagen = null;
        }
        if ($request->hasFile('imagen')) {
            File::ensureDirectoryExists(public_path('uploads/programas'));
            if ($programa->imagen) {
                File::delete(public_path($programa->imagen));
            }
            $nombre = Str::slug($programa->titulo) . '-' . Str::random(6) . '.' . $request->file('imagen')->extension();
            $request->file('imagen')->move(public_path('uploads/programas'), $nombre);
            $programa->imagen = 'uploads/programas/' . $nombre;
        }
        // Miniaturas: quitar las marcadas, ordenar y sumar las nuevas
        $galeria = collect((array) $programa->galeria);
        $quitar = (array) $request->input('quitar_miniaturas', []);
        foreach ($galeria->filter(fn ($r) => in_array($r, $quitar, true)) as $r) {
            File::delete(public_path($r));
        }
        $orden = (array) $request->input('orden_miniaturas', []);
        $galeria = $galeria->reject(fn ($r) => in_array($r, $quitar, true))
            ->sortBy(fn ($r) => (int) ($orden[md5($r)] ?? 999))->values();
        foreach ((array) $request->file('miniaturas', []) as $archivo) {
            File::ensureDirectoryExists(public_path('uploads/programas'));
            $nombre = Str::slug($programa->titulo) . '-' . Str::random(8) . '.' . $archivo->extension();
            $archivo->move(public_path('uploads/programas'), $nombre);
            $galeria->push('uploads/programas/' . $nombre);
        }
        $programa->galeria = $galeria->take(20)->values()->all();
        $programa->save();

        $sync = [];
        foreach ($numeros as $i => $n) {
            $sync[$porNumero[$n]->id] = ['orden' => $i + 1];
        }
        $programa->cursos()->sync($sync);
    }
}
