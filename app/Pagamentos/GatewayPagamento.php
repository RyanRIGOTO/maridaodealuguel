<?php

namespace App\Pagamentos;

/**
 * Ponto de troca do meio de pagamento. Para usar um provedor real (Mercado Pago,
 * PagSeguro, Stripe...), crie uma classe que implemente esta interface e troque o
 * vínculo em AppServiceProvider. O resto do sistema não muda.
 */
interface GatewayPagamento
{
    /** Nome gravado em pagamentos.gateway. */
    public function nome(): string;

    /**
     * @param  string  $metodo  pix, cartao ou boleto
     * @param  string  $descricao  texto que aparece para o pagador
     */
    public function cobrar(float $valor, string $metodo, string $descricao): ResultadoPagamento;
}
