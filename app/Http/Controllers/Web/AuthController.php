<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterClienteRequest;
use App\Http\Requests\RegisterPrestadorRequest;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\CadastroService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Figura 6 - Tela de Login.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect($this->dashboardRoute(Auth::user()));
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // Mesma mensagem para e-mail inexistente, senha errada e conta inativa: não revela qual falhou.
        if (!$user || !Hash::check($request->password, $user->password) || !$user->isActive()) {
            return back()->withErrors(['email' => 'Credenciais inválidas.'])->onlyInput('email');
        }

        Auth::login($user, $request->boolean('lembrar'));
        $request->session()->regenerate();

        AuditLogService::log('login', 'users', $user->id);

        return redirect()->intended($this->dashboardRoute($user));
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLogService::log('logout', 'users', Auth::id());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Figura 7 - Tela de cadastro do Cliente.
     */
    public function showRegisterCliente()
    {
        if (Auth::check()) {
            return redirect($this->dashboardRoute(Auth::user()));
        }

        return view('auth.register-cliente');
    }

    public function registerCliente(RegisterClienteRequest $request)
    {
        $user = CadastroService::cadastrar($request->validated(), 'cliente');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('cliente.dashboard')
            ->with('success', 'Cadastro realizado com sucesso! Bem-vindo(a) ao Maridão de Aluguel.');
    }

    /**
     * Figura 8 - Tela de cadastro do Prestador.
     */
    public function showRegisterPrestador()
    {
        if (Auth::check()) {
            return redirect($this->dashboardRoute(Auth::user()));
        }

        return view('auth.register-prestador');
    }

    public function registerPrestador(RegisterPrestadorRequest $request)
    {
        $user = CadastroService::cadastrar($request->validated(), 'prestador');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('prestador.dashboard')
            ->with('success', 'Cadastro realizado com sucesso! Bem-vindo(a) ao Maridão de Aluguel.');
    }

    private function dashboardRoute(User $user): string
    {
        return match ($user->role) {
            'admin' => route('admin.dashboard'),
            'prestador' => route('prestador.dashboard'),
            default => route('cliente.dashboard'),
        };
    }
}
