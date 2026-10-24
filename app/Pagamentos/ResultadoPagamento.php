<?php

namespace App\Pagamentos;

final class ResultadoPagamento
{
    public function __construct(
        public readonly bool $aprovado,
        public readonly ?string $transacaoId = null,
    ) {}
}
