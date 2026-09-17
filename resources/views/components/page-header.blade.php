@props([
    'title',
    'description' => null,
])

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-ink-900 tracking-tight">{{ $title }}</h1>
        @if ($description)
            <p class="text-sm text-ink-600 mt-1">{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2.5">
            {{ $actions }}
        </div>
    @endif
</div>
