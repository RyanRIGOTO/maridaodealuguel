<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Servico;

class PageController extends Controller
{
    /**
     * Figura 5 - Tela inicial (home).
     */
    public function home()
    {
        $categorias = Categoria::where('status', 'ativo')
            ->withCount('servicos')
            ->orderBy('nome_categoria')
            ->get();

        $servicos = Servico::with(['categoria', 'prestador.prestadorProfile'])
            ->where('status', 'ativo')
            ->whereHas('prestador', fn ($q) => $q->where('status', 'ativo'))
            ->latest()
            ->take(6)
            ->get();

        return view('home', compact('categorias', 'servicos'));
    }
}
