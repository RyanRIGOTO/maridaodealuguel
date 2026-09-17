@props(['value' => 0, 'max' => 5])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5 text-amber-400']) }}>
    @for ($i = 1; $i <= $max; $i++)
        <svg class="w-4 h-4 {{ $i <= round($value) ? 'fill-current' : 'fill-current text-ink-200' }}" viewBox="0 0 20 20">
            <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.1 1.3 6-5.4-3.1-5.4 3.1 1.3-6-4.6-4.1 6.1-.6z"/>
        </svg>
    @endfor
</span>

