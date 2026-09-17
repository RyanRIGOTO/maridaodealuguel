@props(['name' => 'nota'])
<div x-data="{ rating: 0, hover: 0 }" class="inline-flex items-center gap-1">
    <template x-for="i in [1, 2, 3, 4, 5]" :key="i">
        <button
            type="button"
            @click="rating = i"
            @mouseenter="hover = i"
            @mouseleave="hover = 0"
            class="focus:outline-none transition-transform hover:scale-110"
            :aria-label="'Nota ' + i"
        >
            <svg class="w-8 h-8 transition-colors" :class="(hover || rating) >= i ? 'text-amber-400 fill-current' : 'text-ink-200 fill-current'" viewBox="0 0 20 20">
                <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.1 1.3 6-5.4-3.1-5.4 3.1 1.3-6-4.6-4.1 6.1-.6z"/>
            </svg>
        </button>
    </template>
    <input type="hidden" name="{{ $name }}" x-model="rating" required>
</div>

