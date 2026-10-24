<x-auth-layout title="Nova senha — Maridão de Aluguel">
    <div class="text-center sm:text-left">
        <h1 class="text-xl font-bold text-ink-900 tracking-tight">Defina uma nova senha</h1>
        <p class="text-sm text-ink-600 mt-1 mb-6">Código validado. Escolha uma senha com pelo menos 8 caracteres.</p>
    </div>

    <x-flash />

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf

        <div>
            <label for="password" class="form-label">Nova senha</label>
            <input id="password" type="password" name="password" required autofocus minlength="8"
                   autocomplete="new-password" class="form-input" placeholder="••••••••">
        </div>

        <div>
            <label for="password_confirmation" class="form-label">Confirmar nova senha</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8"
                   autocomplete="new-password" class="form-input" placeholder="••••••••">
        </div>

        <button type="submit" class="btn-primary w-full !py-2.5 text-base">
            <i class="ti ti-lock text-lg"></i>
            <span>Redefinir senha</span>
        </button>

        <div class="text-center text-xs sm:text-sm text-ink-600 pt-2">
            <a href="{{ route('login') }}" class="text-brand-600 font-semibold hover:underline">Voltar ao login</a>
        </div>
    </form>
</x-auth-layout>
