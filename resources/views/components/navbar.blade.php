<header class="bg-white border-b border-ink-300 sticky top-0 z-30 shadow-sm">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        {{-- Logotipo Padronizado SVG --}}
        <x-logo variant="lockup" height="h-8 sm:h-10" />

        {{-- Navegação Desktop --}}
        <nav class="hidden sm:flex items-center gap-3">
            @auth
                @php($user = auth()->user())
                <a href="{{ route($user->role.'.dashboard') }}" class="btn-primary btn-sm">
                    <i class="ti ti-layout-dashboard text-base"></i>
                    <span>Meu Painel</span>
                </a>
            @else
                <a href="{{ route('register.cliente') }}" class="btn-ghost">Quero Ser Cliente</a>
                <a href="{{ route('register.prestador') }}" class="btn-ghost">Quero Ser Prestador</a>
                <a href="{{ route('login') }}" class="btn-primary btn-sm">
                    <i class="ti ti-login text-base"></i>
                    <span>Entrar</span>
                </a>
            @endauth
        </nav>

        {{-- Botão Menu Mobile --}}
        <button class="sm:hidden btn-ghost !p-2 text-ink-700" @click="mobileMenu = !mobileMenu" aria-label="Abrir menu">
            <i class="ti ti-menu-2 text-xl"></i>
        </button>
    </div>

    {{-- Dropdown Mobile --}}
    <div x-show="mobileMenu" x-cloak class="sm:hidden border-t border-ink-300 bg-white px-4 py-3 space-y-2 shadow-lg">
        @auth
            <a href="{{ route(auth()->user()->role.'.dashboard') }}" class="block btn-primary w-full text-center">Meu Painel</a>
        @else
            <a href="{{ route('register.cliente') }}" class="block btn-ghost w-full text-center">Quero Ser Cliente</a>
            <a href="{{ route('register.prestador') }}" class="block btn-ghost w-full text-center">Quero Ser Prestador</a>
            <a href="{{ route('login') }}" class="block btn-primary w-full text-center">Entrar</a>
        @endauth
    </div>
</header>
