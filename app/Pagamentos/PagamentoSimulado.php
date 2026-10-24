<?php

namespace App\Pagamentos;

use Illuminate\Support\Str;

/**
 * Pagamento fictício para desenvolvimento e apresentação do TCC: aprova na hora,
 * sem cobrar ninguém e sem receber dados de cartão.
 * shortcut: não há cobrança real; trocar por um gateway antes de ir para produção.
 */
class PagamentoSimulado implements GatewayPagamento
{
    public function nome(): string
    {
        return 'simulado';
    }

    public function cobrar(float $valor, string $metodo, string $descricao): ResultadoPagamento
    {
        return new ResultadoPagamento(true, 'SIM-'.Str::upper(Str::random(12)));
    }
}
