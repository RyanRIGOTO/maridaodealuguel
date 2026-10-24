@props(['valor'])
{{-- Preço de serviço: "Grátis" quando é zero. --}}
@if ((float) $valor <= 0)
Grátis
@else
R$ {{ number_format($valor, 2, ',', '.') }}
@endif
