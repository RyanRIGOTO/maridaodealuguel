<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Encerra o acesso de quem foi inativado depois de entrar no sistema.
 * Sem isso, a sessão (Web) ou o token (API) continuavam valendo até expirar.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->isActive()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            $user->tokens()->delete();

            return response()->json([
                'success' => false,
                'error' => ['code' => 'INACTIVE', 'message' => 'Conta inativa. Entre em contato com o suporte.'],
            ], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors(['email' => 'Conta inativa. Entre em contato com o suporte.']);
    }
}
