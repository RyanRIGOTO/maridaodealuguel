<x-auth-layout title="Cadastro de Cliente — Maridão de Aluguel">
    <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-600 hover:text-brand-600 transition-colors mb-3">
        <i class="ti ti-arrow-left text-sm"></i>
        <span>Voltar ao início</span>
    </a>

    <div class="mb-6">
        <h1 class="text-xl font-bold text-ink-900 tracking-tight">Cadastro de Cliente</h1>
        <p class="text-sm text-ink-600 mt-1">Crie sua conta gratuita para solicitar e agendar serviços.</p>
    </div>

    <x-flash />

    <form method="POST" action="{{ route('register.cliente.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="form-label">Nome Completo <span class="text-rose-500">*</span></label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                   class="form-input" placeholder="Seu nome e sobrenome">
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="email" class="form-label">E-mail <span class="text-rose-500">*</span></label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="100"
                       class="form-input" placeholder="seu@email.com">
            </div>
            <div>
                <label for="phone" class="form-label">Telefone / WhatsApp</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" maxlength="20"
                       class="form-input" placeholder="(00) 00000-0000">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="cpf_cnpj" class="form-label">CPF <span class="text-rose-500">*</span></label>
                <input id="cpf_cnpj" type="text" name="cpf_cnpj" value="{{ old('cpf_cnpj') }}" required maxlength="18" inputmode="numeric"
                       class="form-input" placeholder="000.000.000-00">
            </div>
            <div>
                <label for="data_nascimento" class="form-label">Data de Nascimento <span class="text-rose-500">*</span></label>
                <input id="data_nascimento" type="date" name="data_nascimento" value="{{ old('data_nascimento') }}" required
                       class="form-input">
            </div>
        </div>

        <fieldset class="border-t border-ink-200 pt-5">
            <legend class="text-sm font-bold text-ink-900 px-1">Endereço Principal</legend>
            <div class="mt-3">
                <x-address-fields />
            </div>
        </fieldset>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="password" class="form-label">Senha <span class="text-rose-500">*</span></label>
                <input id="password" type="password" name="password" required minlength="8"
                       class="form-input" placeholder="Mínimo 8 caracteres">
            </div>
            <div>
                <label for="password_confirmation" class="form-label">Confirmar Senha <span class="text-rose-500">*</span></label>
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8"
                       class="form-input">
            </div>
        </div>

        <label class="flex items-start gap-2.5 text-xs sm:text-sm text-ink-600 cursor-pointer pt-1">
            <input type="checkbox" name="termos_lgpd" value="1" required
                   class="mt-0.5 rounded border-ink-300 text-brand-500 focus:ring-brand-500/20">
            <span>Concordo com os <span class="text-brand-600 font-semibold underline">Termos de Uso</span> e a <span class="text-brand-600 font-semibold underline">Política de Privacidade</span> (LGPD).</span>
        </label>

        <button type="submit" class="btn-primary w-full !py-2.5 text-base mt-2">
            <i class="ti ti-user-check text-lg"></i>
            <span>Criar Conta de Cliente</span>
        </button>

        <div class="text-center text-xs sm:text-sm text-ink-600 pt-3 space-y-1">
            <p>Já possui cadastro? <a href="{{ route('login') }}" class="text-brand-600 font-semibold hover:underline">Fazer login</a></p>
            <p>Deseja prestar serviços? <a href="{{ route('register.prestador') }}" class="text-brand-600 font-semibold hover:underline">Cadastre-se como Prestador</a></p>
        </div>
    </form>
</x-auth-layout>
