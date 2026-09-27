@props([
    'user',
    'subtitle' => null,
])

<header class="no-print h-16 bg-white border-b border-ink-300 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-20 shadow-sm">
    <div class="flex items-center gap-3">
        <button class="lg:hidden btn-ghost !p-2" type="button" data-menu-lateral aria-controls="menu-lateral" aria-expanded="false" title="Abrir menu">
            <i class="ti ti-menu-2 text-xl"></i>
        </button>
        <div>
            <p class="text-sm font-semibold text-ink-900 leading-none">Olá, {{ explode(' ', $user->name)[0] }}!</p>
            @if ($subtitle)
                <p class="text-xs text-ink-600 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('chat.index') }}" class="btn-ghost !p-2 text-ink-600 hover:text-brand-600 relative" title="Mensagens">
            <i class="ti ti-message-circle text-xl"></i>
        </a>
        <div class="w-9 h-9 rounded-full bg-brand-500 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-sm" title="{{ $user->name }}">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
    </div>
</header>
