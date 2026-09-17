<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\PrestadorProfile;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AvaliacaoService
{
    /**
     * RN8 & RN10: Cria avaliacao e recalcula reputacao do prestador.
     */
    public static function criar(int $agendamentoId, int $clienteId, int $nota, ?string $comentario): Avaliacao
    {
        $agendamento = Agendamento::with('avaliacao')->findOrFail($agendamentoId);

        if ($agendamento->cliente_id !== $clienteId) {
            throw ValidationException::withMessages(['agendamento' => 'Você só pode avaliar seus próprios agendamentos.']);
        }
        if ($agendamento->status !== 'concluido') {
            throw ValidationException::withMessages(['agendamento' => 'Apenas serviços concluídos podem ser avaliados.']);
        }
        if ($agendamento->avaliacao) {
            throw ValidationException::withMessages(['agendamento' => 'Este agendamento já foi avaliado.']);
        }

        $avaliacao = Avaliacao::create([
            'agendamento_id' => $agendamento->id,
            'cliente_id' => $clienteId,
            'prestador_id' => $agendamento->prestador_id,
            'nota' => $nota,
            'comentario' => $comentario,
            'data_avaliacao' => now(),
        ]);

        // RN10: Recalcular reputacao do prestador
        static::atualizarReputacao($agendamento->prestador_id);

        AuditLogService::log('avaliacao_criada', 'avaliacoes', $avaliacao->id, [
            'agendamento_id' => $agendamentoId,
            'nota' => $nota,
        ]);

        return $avaliacao;
    }

    /**
     * RN10: Atualizar reputacao media do prestador. Se < 2.5, em_revisao = true.
     */
    public static function atualizarReputacao(int $prestadorId): void
    {
        $media = Avaliacao::where('prestador_id', $prestadorId)
            ->where('moderada', false)
            ->avg('nota');

        $prestador = User::find($prestadorId);

        if ($prestador && $prestador->prestadorProfile) {
            $emRevisao = ($media !== null && $media < 2.5);
            $prestador->prestadorProfile->update([
                'reputacao_media' => round($media ?? 5.00, 2),
                'em_revisao' => $emRevisao,
            ]);

            if ($emRevisao) {
                AuditLogService::log('prestador_em_revisao', 'users', $prestadorId, [
                    'reputacao_media' => round($media, 2),
                ]);
            }
        }
    }

    /**
     * RN9: Moderacao de avaliacao (admin only).
     */
    public static function moderar(Avaliacao $avaliacao, bool $moderada, ?string $justificativa, int $adminId): Avaliacao
    {
        $avaliacao->update(['moderada' => $moderada]);

        AuditLogService::log('avaliacao_moderada', 'avaliacoes', $avaliacao->id, [
            'admin_id' => $adminId,
            'moderada' => $moderada,
            'justificativa' => $justificativa,
        ]);

        // Recalcular reputacao apos moderacao
        static::atualizarReputacao($avaliacao->prestador_id);

        return $avaliacao;
    }
}