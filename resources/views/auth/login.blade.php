<x-auth-layout title="Entrar — Maridão de Aluguel">
    <div class="text-center sm:text-left">
        <h1 class="text-xl font-bold text-ink-900 tracking-tight">Acesse sua conta</h1>
        <p class="text-sm text-ink-600 mt-1 mb-6">Entre com seu e-mail e senha para continuar.</p>
    </div>

    <x-flash />

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="form-label">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="form-input" placeholder="seu@email.com">
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="form-label !mb-0">Senha</label>
            </div>
            <input id="password" type="password" name="password" required
                   class="form-input" placeholder="••••••••">
        </div>

    {{-- preciso implementar isso depois. --}}
        {{-- <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-xs sm:text-sm text-ink-600 cursor-pointer">
                <input type="checkbox" name="lembrar" class="rounded border-ink-300 text-brand-500 focus:ring-brand-500/20">
                <span>Lembrar-me neste dispositivo</span>
            </label>
        </div> --}}

        <button type="submit" class="btn-primary w-full !py-2.5 text-base">
            <i class="ti ti-login text-lg"></i>
            <span>Entrar</span>
        </button>

        <div class="text-center text-xs sm:text-sm text-ink-600 pt-2">
            Ainda não tem conta?
            <br>
            <a href="{{ route('register.cliente') }}" class="text-brand-600 font-semibold hover:underline">Sou Cliente</a>
            <span class="mx-1 text-ink-300">-</span>
            <a href="{{ route('register.prestador') }}" class="text-brand-600 font-semibold hover:underline">Sou Prestador</a>
        </div>
    </form>

          
        {{-- testar os logins com os usuários de teste abaixo: --}}
        {{-- <div class="space-y-1 font-mono text-[11px] text-ink-700">
            <p><span class="text-ink-900 font-semibold">Admin:</span> admin@maridaodealuguel.com.br / senha123</p>
            <p><span class="text-ink-900 font-semibold">Prestador:</span> joao.silva@email.com / senha123</p>
            <p><span class="text-ink-900 font-semibold">Cliente:</span> ana.costa@email.com / senha123</p>
        </div> --}}
    {{-- </div> --}}
    
</x-auth-layout>

