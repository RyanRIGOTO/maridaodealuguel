<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Pagamento;
use App\Pagamentos\GatewayPagamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PagamentoService
{
    public function __construct(private GatewayPagamento $gateway) {}

    /**
     * RN4: agendamentos do cliente cancelados com multa ainda não paga.
     */
    public static function multasPendentes(int $clienteId): Builder
    {
        return Agendamento::where('cliente_id', $clienteId)
            ->where('status', 'cancelado')
            ->whereHas('cancelamento', fn ($q) => $q->where('multa', '>', 0))
            ->whereDoesntHave('pagamentos', fn ($q) => $q->where('tipo', 'multa')->where('status', 'aprovado'));
    }

    /**
     * Cobra a multa pelo gateway. Aprovada, a multa vira recebimento do prestador (RN4).
     *
     * @throws ValidationException
     */
    public function pagarMulta(Agendamento $agendamento, int $clienteId, string $metodo): Pagamento
    {
        $pagamento = DB::transaction(function () use ($agendamento, $clienteId, $metodo) {
            // Trava o agendamento para que dois cliques não paguem a mesma multa duas vezes.
            $agendamento = Agendamento::whereKey($agendamento->id)->lockForUpdate()->firstOrFail();

            if (!static::multasPendentes($clienteId)->whereKey($agendamento->id)->exists()) {
                throw ValidationException::withMessages(['multa' => ['Não há multa pendente para este agendamento.']]);
            }

            $valor = (float) $agendamento->cancelamento->multa;
            $descricao = 'Multa de cancelamento: '.($agendamento->servico?->nome ?? 'serviço').' em '.$agendamento->data_hora->format('d/m/Y H:i');
            $resultado = $this->gateway->cobrar($valor, $metodo, $descricao);

            $pagamento = Pagamento::create([
                'agendamento_id' => $agendamento->id,
                'tipo' => 'multa',
                'valor' => $valor,
                'metodo' => $metodo,
                'status' => $resultado->aprovado ? 'aprovado' : 'recusado',
                'gateway' => $this->gateway->nome(),
                'transacao_id' => $resultado->transacaoId,
                'pago_em' => $resultado->aprovado ? now() : null,
            ]);

            AuditLogService::log($resultado->aprovado ? 'multa_paga' : 'multa_pagamento_recusado', 'pagamentos', $pagamento->id, [
                'agendamento_id' => $agendamento->id,
                'valor' => $valor,
                'metodo' => $metodo,
                'gateway' => $pagamento->gateway,
            ]);

            if ($resultado->aprovado) {
                RecebimentoService::criarMulta($agendamento, $valor);
            }

            return $pagamento;
        });

        // Fora da transação, para a tentativa recusada continuar registrada.
        if ($pagamento->status !== 'aprovado') {
            throw ValidationException::withMessages(['multa' => ['Pagamento recusado. Tente outro método.']]);
        }

        return $pagamento;
    }
}
