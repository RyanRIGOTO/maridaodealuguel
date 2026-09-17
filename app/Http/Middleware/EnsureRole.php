<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege as rotas web por papel de usuário (cliente, prestador, admin).
 * Diferente do middleware role (usado pela API, que responde JSON 403),
 * este middleware é pensado para o front-end em Blade: se o usuário logado
 * não tiver o papel esperado, ele é redirecionado de volta com uma mensagem,
 * em vez de receber um erro cru.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== $role) {
            return redirect()->route('home')
                ->with('error', 'Você não tem permissão para acessar essa área.');
        }

        return $next($request);
    }
}
