<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\PrestadorProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
            'nota' => $nota,
            'comentario' => $comentario,
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
     * RN10: nota média abaixo deste valor por DIAS_PARA_REVISAO dias seguidos coloca o prestador em revisão.
     */
    public const NOTA_MINIMA = 2.5;
    public const DIAS_PARA_REVISAO = 30;

    /**
     * RN10: média das avaliações não moderadas do prestador (null se ainda não tem nenhuma).
     * É calculada na hora, não fica gravada: assim nunca fica desatualizada.
     */
    public static function media(int $prestadorId): ?float
    {
        $media = Avaliacao::doPrestador($prestadorId)->where('moderada', false)->avg('nota');

        return $media === null ? null : round((float) $media, 2);
    }

    /**
     * Médias de vários prestadores numa consulta só (listagens), indexadas pelo id do prestador.
     *
     * @param  array<int>  $prestadorIds
     * @return array<int, float>
     */
    public static function medias(array $prestadorIds): array
    {
        if ($prestadorIds === []) {
            return [];
        }

        return DB::table('avaliacoes')
            ->join('agendamentos', 'agendamentos.id', '=', 'avaliacoes.agendamento_id')
            ->join('servicos', 'servicos.id', '=', 'agendamentos.servico_id')
            ->where('avaliacoes.moderada', false)
            ->whereIn('servicos.prestador_id', $prestadorIds)
            ->groupBy('servicos.prestador_id')
            ->selectRaw('servicos.prestador_id as prestador, ROUND(AVG(avaliacoes.nota), 2) as media')
            ->pluck('media', 'prestador')
            ->map(fn ($media) => (float) $media)
            ->all();
    }

    /**
     * RN10: Recalcula a situação do prestador e controla o período abaixo da nota mínima.
     */
    public static function atualizarReputacao(int $prestadorId): void
    {
        $media = static::media($prestadorId);

        $perfil = User::find($prestadorId)?->prestadorProfile;

        if (!$perfil) {
            return;
        }

        $abaixo = $media !== null && $media < self::NOTA_MINIMA;

        $perfil->update([
            // Começa a contar na primeira vez abaixo; zera quando a média se recupera.
            'abaixo_media_desde' => $abaixo ? ($perfil->abaixo_media_desde ?? now()) : null,
        ]);

        static::verificarRevisao($perfil);
    }

    /**
     * RN10: coloca o prestador em revisão quando a média está abaixo da mínima há
     * DIAS_PARA_REVISAO dias seguidos. A revisão só termina por ação do administrador.
     * Retorna se o prestador está em revisão.
     */
    public static function verificarRevisao(PrestadorProfile $perfil): bool
    {
        if ($perfil->em_revisao) {
            return true;
        }

        $prazoCumprido = $perfil->abaixo_media_desde !== null
            && $perfil->abaixo_media_desde->lte(now()->subDays(self::DIAS_PARA_REVISAO));

        if (!$prazoCumprido) {
            return false;
        }

        $perfil->update(['em_revisao' => true]);

        // RN10 + RN14: o alerta ao administrador aparece no dashboard e fica na auditoria.
        AuditLogService::log('prestador_em_revisao', 'users', $perfil->user_id, [
            'reputacao_media' => (float) $perfil->reputacao_media,
            'abaixo_media_desde' => $perfil->abaixo_media_desde->toISOString(),
        ]);

        return true;
    }

    /**
     * RN10: verifica todos os prestadores cujo prazo já venceu (rotina diária e dashboard do admin).
     */
    public static function verificarRevisoesPendentes(): int
    {
        $perfis = PrestadorProfile::where('em_revisao', false)
            ->whereNotNull('abaixo_media_desde')
            ->where('abaixo_media_desde', '<=', now()->subDays(self::DIAS_PARA_REVISAO))
            ->get();

        return $perfis->filter(fn (PrestadorProfile $perfil) => static::verificarRevisao($perfil))->count();
    }

    /**
     * RN10: o administrador conclui a revisão e libera o prestador para novos agendamentos.
     * Se a média continuar baixa, um novo período de 30 dias começa a contar.
     */
    public static function concluirRevisao(PrestadorProfile $perfil, int $adminId, ?string $observacao): void
    {
        $abaixo = (float) $perfil->reputacao_media < self::NOTA_MINIMA;

        $perfil->update([
            'em_revisao' => false,
            'abaixo_media_desde' => $abaixo ? now() : null,
        ]);

        AuditLogService::log('revisao_prestador_concluida', 'users', $perfil->user_id, [
            'admin_id' => $adminId,
            'observacao' => $observacao,
        ]);
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