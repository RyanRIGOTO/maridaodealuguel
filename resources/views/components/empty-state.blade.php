@props([
    'icon' => 'ti-inbox',
    'title' => 'Nenhum registro encontrado',
    'description' => null,
])

<div class="card p-8 sm:p-12 text-center bg-white border border-ink-300">
    <div class="w-14 h-14 rounded-2xl bg-ink-100 text-ink-500 flex items-center justify-center mx-auto mb-4">
        <i class="ti {{ $icon }} text-2xl"></i>
    </div>
    <h3 class="text-base font-bold text-ink-900">{{ $title }}</h3>
    @if ($description)
        <p class="text-sm text-ink-600 mt-1 max-w-md mx-auto leading-relaxed">{{ $description }}</p>
    @endif
    @if (isset($action))
        <div class="mt-5">
            {{ $action }}
        </div>
    @endif
</div>
