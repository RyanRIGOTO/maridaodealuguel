<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Itens por página vindos de ?per_page, limitados para que uma requisição
     * não consiga despejar a tabela inteira de uma vez.
     */
    protected function porPagina(Request $request, int $padrao): int
    {
        return min(max($request->integer('per_page', $padrao), 1), 100);
    }
}
