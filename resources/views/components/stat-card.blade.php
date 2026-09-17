@props([
    'label',
    'value',
    'icon' => null,
    'color' => 'brand', // 'brand', 'emerald', 'amber', 'rose', 'sky', 'ink'
    'hint' => null,
])

@php
    $colorMap = [
        'brand' => ['bg' => 'bg-brand-50', 'text' => 'text-brand-600'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600'],
        'rose' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-600'],
        'sky' => ['bg' => 'bg-sky-50', 'text' => 'text-sky-600'],
        'ink' => ['bg' => 'bg-ink-100', 'text' => 'text-ink-700'],
    ];
    $c = $colorMap[$color] ?? $colorMap['brand'];
@endphp

<div class="card p-5 bg-white border border-ink-300 shadow-sm flex items-start justify-between gap-4">
    <div class="min-w-0 flex-1">
        <p class="text-xs font-semibold uppercase tracking-wider text-ink-600 truncate">{{ $label }}</p>
        <p class="text-2xl sm:text-3xl font-bold text-ink-900 mt-1 tracking-tight">{{ $value }}</p>
        @if ($hint)
            <p class="text-xs text-ink-500 mt-1.5 truncate">{{ $hint }}</p>
        @endif
    </div>
    @if ($icon)
        <div class="w-11 h-11 rounded-xl {{ $c['bg'] }} {{ $c['text'] }} flex items-center justify-center shrink-0 shadow-sm">
            <i class="ti {{ $icon }} text-xl"></i>
        </div>
    @endif
</div>
