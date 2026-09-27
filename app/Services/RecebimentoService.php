<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Recebimento;
use Carbon\Carbon;

class RecebimentoService
{
    /**
     * RN7: Criar recebimento com taxa admin 5% e RN6: escrow/liberação 48h.
     */
    public static function criar(Agendamento $agendamento): Recebimento
    {
        $valorTotal = $agendamento->preco_acordado;
        $taxaAdmin = round($valorTotal * 0.05, 2); // RN7: 5%
        $valorLiquido = $valorTotal - $taxaAdmin;
        $dataLiberacao = now()->addHours(48); // RN6: 48h security hold

        $recebimento = Recebimento::create([
            'agendamento_id' => $agendamento->id,
            'valor_total' => $valorTotal,
            'taxa_admin' => $taxaAdmin,
            'valor_liquido_prestador' => $valorLiquido,
            'status_recebimento' => 'pendente',
            'data_liberacao' => $dataLiberacao,
        ]);

        AuditLogService::log('recebimento_criado', 'recebimentos', $recebimento->id, [
            'agendamento_id' => $agendamento->id,
            'valor_total' => $valorTotal,
            'taxa_admin' => $taxaAdmin,
            'valor_liquido' => $valorLiquido,
            'data_liberacao' => $dataLiberacao->toISOString(),
        ]);

        return $recebimento;
    }

    /**
     * Liberar recebimento = pagar (muda status para 'pago')
     */
    public function liberar(Recebimento $recebimento): Recebimento
    {
        $recebimento->update([
            'status_recebimento' => 'pago',
            'data_liberacao' => now(),
        ]);

        AuditLogService::log('recebimento_liberado', 'recebimentos', $recebimento->id);
        return $recebimento;
    }
}