@props(['name' => 'nota'])

<fieldset data-avaliacao class="inline-flex items-center gap-1">
    <legend class="sr-only">Nota do atendimento</legend>
    @for ($nota = 1; $nota <= 5; $nota++)
        <label class="cursor-pointer transition-transform hover:scale-110">
            <input type="radio" name="{{ $name }}" value="{{ $nota }}" required
                   class="peer sr-only" aria-label="Nota {{ $nota }}">
            <svg data-estrela="{{ $nota }}" class="w-8 h-8 fill-current text-ink-200 transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500"
                 viewBox="0 0 20 20" aria-hidden="true">
                <path d="M10 1.5l2.6 5.6 6.1.6-4.6 4.1 1.3 6-5.4-3.1-5.4 3.1 1.3-6-4.6-4.1 6.1-.6z"/>
            </svg>
        </label>
    @endfor
</fieldset>
