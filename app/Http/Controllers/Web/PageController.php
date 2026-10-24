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
        // Conta só os serviços que o visitante consegue de fato contratar.
        $categorias = Categoria::where('status', 'ativo')
            ->withCount(['servicos' => fn ($q) => $q->contratavel()])
            ->orderBy('nome_categoria')
            ->get();

        $servicos = Servico::with(['categoria', 'prestador.prestadorProfile'])
            ->contratavel()
            ->latest()
            ->take(6)
            ->get();

        return view('home', compact('categorias', 'servicos'));
    }
}
