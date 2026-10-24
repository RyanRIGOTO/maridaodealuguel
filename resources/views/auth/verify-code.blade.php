<x-auth-layout title="Validar código — Maridão de Aluguel">
    <div class="text-center sm:text-left">
        <h1 class="text-xl font-bold text-ink-900 tracking-tight">Validar código</h1>
        <p class="text-sm text-ink-600 mt-1 mb-6">
            Digite o código enviado para <span class="font-semibold text-ink-900">{{ $email }}</span>.
            Ele vale por {{ \App\Services\PasswordResetService::VALIDADE_MINUTOS }} minutos.
        </p>
    </div>

    <x-flash />

    <form method="POST" action="{{ route('password.code.verify') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="form-label">Código</label>
            <input id="code" type="text" name="code" required autofocus
                   inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
                   class="form-input text-center text-2xl tracking-[0.5em] font-semibold" placeholder="000000">
        </div>

        <button type="submit" class="btn-primary w-full !py-2.5 text-base">
            <i class="ti ti-shield-check text-lg"></i>
            <span>Validar código</span>
        </button>
    </form>

    <form method="POST" action="{{ route('password.email') }}" class="text-center text-xs sm:text-sm text-ink-600 pt-4">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        Não recebeu?
        <button type="submit" class="text-brand-600 font-semibold hover:underline">Enviar outro código</button>
        <span class="mx-1 text-ink-300">-</span>
        <a href="{{ route('password.request') }}" class="text-brand-600 font-semibold hover:underline">Trocar e-mail</a>
    </form>
</x-auth-layout>
