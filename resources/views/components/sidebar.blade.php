@props(['user'])

<aside
    class="no-print fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-ink-300 flex flex-col transform transition-transform duration-200 lg:translate-x-0 lg:static lg:shrink-0 shadow-sm lg:shadow-none"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    {{-- Logotipo da Marca com Componente Padronizado --}}
    <div class="h-16 flex items-center px-5 border-b border-ink-300">
        <x-logo variant="lockup" height="h-9 sm:h-10" />
    </div>

    {{-- Links de Navegação Conforme o Papel (Role) --}}
    <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto">
        @if ($user->role === 'cliente')
            <a href="{{ route('cliente.dashboard') }}" class="sidebar-link {{ request()->routeIs('cliente.dashboard') ? 'active' : '' }}">
                <i class="ti ti-home text-lg"></i>
                <span>Início</span>
            </a>
            <a href="{{ route('cliente.agendar') }}" class="sidebar-link {{ request()->routeIs('cliente.agendar') ? 'active' : '' }}">
                <i class="ti ti-calendar-plus text-lg"></i>
                <span>Agendar Serviço</span>
            </a>
            <a href="{{ route('cliente.historico') }}" class="sidebar-link {{ request()->routeIs('cliente.historico') ? 'active' : '' }}">
                <i class="ti ti-history text-lg"></i>
                <span>Histórico</span>
            </a>
            <a href="{{ route('cliente.avaliacoes') }}" class="sidebar-link {{ request()->routeIs('cliente.avaliacoes') ? 'active' : '' }}">
                <i class="ti ti-star text-lg"></i>
                <span>Avaliações</span>
            </a>
            <a href="{{ route('chat.index') }}" class="sidebar-link {{ request()->routeIs('chat.*') ? 'active' : '' }}">
                <i class="ti ti-message-circle text-lg"></i>
                <span>Mensagens</span>
            </a>
        @elseif ($user->role === 'prestador')
            <a href="{{ route('prestador.dashboard') }}" class="sidebar-link {{ request()->routeIs('prestador.dashboard') ? 'active' : '' }}">
                <i class="ti ti-home text-lg"></i>
                <span>Início</span>
            </a>
            <a href="{{ route('prestador.agendamentos') }}" class="sidebar-link {{ request()->routeIs('prestador.agendamentos') ? 'active' : '' }}">
                <i class="ti ti-calendar-check text-lg"></i>
                <span>Agendamentos</span>
            </a>
            <a href="{{ route('prestador.servicos') }}" class="sidebar-link {{ request()->routeIs('prestador.servicos') ? 'active' : '' }}">
                <i class="ti ti-briefcase text-lg"></i>
                <span>Meus Serviços</span>
            </a>
            <a href="{{ route('prestador.historico') }}" class="sidebar-link {{ request()->routeIs('prestador.historico') ? 'active' : '' }}">
                <i class="ti ti-history text-lg"></i>
                <span>Histórico</span>
            </a>
            <a href="{{ route('prestador.relatorios.servicos') }}" class="sidebar-link {{ request()->routeIs('prestador.relatorios.*') ? 'active' : '' }}">
                <i class="ti ti-chart-bar text-lg"></i>
                <span>Relatório de Serviços</span>
            </a>
            <a href="{{ route('chat.index') }}" class="sidebar-link {{ request()->routeIs('chat.*') ? 'active' : '' }}">
                <i class="ti ti-message-circle text-lg"></i>
                <span>Mensagens</span>
            </a>
        @else
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="ti ti-home text-lg"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('admin.usuarios') }}" class="sidebar-link {{ request()->routeIs('admin.usuarios') ? 'active' : '' }}">
                <i class="ti ti-users text-lg"></i>
                <span>Usuários</span>
            </a>
            <a href="{{ route('admin.categorias') }}" class="sidebar-link {{ request()->routeIs('admin.categorias') ? 'active' : '' }}">
                <i class="ti ti-tag text-lg"></i>
                <span>Categorias</span>
            </a>
            <a href="{{ route('admin.relatorios') }}" class="sidebar-link {{ request()->routeIs('admin.relatorios*') ? 'active' : '' }}">
                <i class="ti ti-chart-bar text-lg"></i>
                <span>Relatórios</span>
            </a>
        @endif
    </nav>

    {{-- Informações da Sessão e Logout --}}
    <div class="p-3 border-t border-ink-300 bg-ink-50/50">
        <div class="px-3 py-1.5 mb-1 text-xs text-ink-600">
            Acesso como <span class="font-semibold text-ink-800 capitalize">{{ $user->role }}</span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar-link w-full text-rose-600 hover:bg-rose-50 hover:text-rose-700">
                <i class="ti ti-logout text-lg"></i>
                <span>Sair da conta</span>
            </button>
        </form>
    </div>
</aside>
