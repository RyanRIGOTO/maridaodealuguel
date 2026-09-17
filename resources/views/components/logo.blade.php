@props([
    'variant' => 'lockup', // 'lockup' (símbolo + texto) ou 'mark' (apenas símbolo)
    'height' => 'h-9',
    'href' => route('home'),
    'alt' => 'Maridão de Aluguel',
    'class' => '',
])

@php
    $src = $variant === 'mark' ? asset('images/logo-mark.svg') : asset('images/logo-lockup.svg');
    // Mantém a proporção do SVG e impede que o logotipo ultrapasse o contêiner.
    $defaultClasses = 'w-auto max-w-full shrink-0 object-contain transition-transform duration-150';
    $imgClass = "{$height} {$defaultClasses} {$class}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex max-w-full items-center focus:outline-none focus:ring-2 focus:ring-brand-500/20 rounded-lg group']) }} aria-label="{{ $alt }}">
        <img src="{{ $src }}" alt="{{ $alt }}" class="{{ $imgClass }} group-hover:opacity-95" />
    </a>
@else
    <img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => $imgClass]) }} />
@endif
