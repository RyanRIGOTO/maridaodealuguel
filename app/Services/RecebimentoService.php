<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Configuracao;
use App\Models\Recebimento;

class RecebimentoService
{
    /**
     * RN7: Cria o recebimento com a taxa da plataforma definida pelo administrador
     * e RN6: retenção (escrow) com liberação em 48h.
     */
    public static function criar(Agendamento $agendamento): ?Recebimento
    {
        // Serviço grátis não gera cobrança nem repasse.
        if ((float) $agendamento->preco_acordado <= 0) {
            return null;
        }

        return static::gerar($agendamento, (float) $agendamento->preco_acordado, 'servico');
    }

    /**
     * RN4: a multa de cancelamento tardio compensa o prestador. Ela segue o mesmo
     * fluxo financeiro do serviço (taxa da plataforma e liberação em 48h).
     */
    public static function criarMulta(Agendamento $agendamento, float $multa): Recebimento
    {
        return static::gerar($agendamento, $multa, 'multa');
    }

    private static function gerar(Agendamento $agendamento, float $valorTotal, string $tipo): Recebimento
    {
        $percentual = Configuracao::taxaPlataforma();
        $taxaAdmin = round($valorTotal * $percentual / 100, 2); // RN7
        $valorLiquido = round($valorTotal - $taxaAdmin, 2);
        $dataLiberacao = now()->addHours(48); // RN6: 48h security hold

        $recebimento = Recebimento::create([
            'agendamento_id' => $agendamento->id,
            'tipo' => $tipo,
            'valor_total' => $valorTotal,
            'percentual_taxa' => $percentual, // taxa e líquido são calculados a partir dele
            'status_recebimento' => 'pendente',
            'data_liberacao' => $dataLiberacao,
        ]);

        AuditLogService::log('recebimento_criado', 'recebimentos', $recebimento->id, [
            'agendamento_id' => $agendamento->id,
            'tipo' => $tipo,
            'valor_total' => $valorTotal,
            'taxa_admin' => $taxaAdmin,
            'percentual_taxa' => $percentual,
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