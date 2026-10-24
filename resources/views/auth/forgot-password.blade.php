<x-auth-layout title="Esqueci minha senha — Maridão de Aluguel">
    <div class="text-center sm:text-left">
        <h1 class="text-xl font-bold text-ink-900 tracking-tight">Esqueci minha senha</h1>
        <p class="text-sm text-ink-600 mt-1 mb-6">Informe o e-mail da sua conta. Se ele estiver cadastrado, enviaremos um código de 6 dígitos.</p>
    </div>

    <x-flash />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="form-label">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="form-input" placeholder="seu@email.com">
        </div>

        <button type="submit" class="btn-primary w-full !py-2.5 text-base">
            <i class="ti ti-mail text-lg"></i>
            <span>Enviar código</span>
        </button>

        <div class="text-center text-xs sm:text-sm text-ink-600 pt-2">
            <a href="{{ route('login') }}" class="text-brand-600 font-semibold hover:underline">Voltar ao login</a>
        </div>
    </form>
</x-auth-layout>
