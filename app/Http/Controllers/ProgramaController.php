<?php

namespace App\Http\Controllers;

use App\Models\Programa;
use App\Support\Carrito;
use Illuminate\View\View;

class ProgramaController extends Controller
{
    /** Página con todos los programas: buscador, filtro por área y orden */
    public function index(): View
    {
        $programas = Programa::publicados()->with('cursos:id,numero,titulo,instructor,precio,horas')->get();
        $porArea = $programas->countBy('area');

        return view('programas.index', [
            'programas' => $programas,
            'areas' => collect(Programa::AREAS)->filter(fn ($a, $k) => $porArea->has($k))->map(fn ($a, $k) => [...$a, $porArea[$k]]),
            'totalCursos' => $programas->flatMap->cursos->unique('id')->count(),
        ]);
    }

    public function show(Programa $programa): View
    {
        abort_unless($programa->publicado, 404);
        $programa->load('cursos');

        $otros = Programa::publicados()->where('id', '!=', $programa->id)->with('cursos')->get()
            ->sortByDesc(fn ($p) => ($p->color === $programa->color ? 2 : 0) + ($p->nombre === $programa->nombre ? 3 : 0))
            ->take(8)->values();

        return view('programas.show', [
            'programa' => $programa,
            'otros' => $otros,
            'enCarrito' => Carrito::tiene($programa),
        ]);
    }
}
