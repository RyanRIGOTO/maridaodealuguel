@props(['status'])
@php
    $labels = [
        'pendente' => 'Pendente',
        'confirmado' => 'Confirmado',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado',
        'ativo' => 'Ativo',
        'inativo' => 'Inativo',
        'pago' => 'Pago',
    ];
    $label = $labels[$status] ?? ucfirst($status);
@endphp
<span {{ $attributes->merge(['class' => 'badge-'.$status]) }}>{{ $label }}</span>
