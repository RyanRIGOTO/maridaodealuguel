<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Recebimento;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    /**
     * RN13: Relatorios estatisticos com filtros (data, status, prestador).
     */
    public function servicosPorCategoria(Request $request): JsonResponse
    {
        $query = Agendamento::with(['servico.categoria', 'prestador', 'cliente'])
            ->when($request->filled('data_inicio'), fn($q) => $q->whereDate('data_hora', '>=', $request->data_inicio))
            ->when($request->filled('data_fim'), fn($q) => $q->whereDate('data_hora', '<=', $request->data_fim))
            ->when($request->filled('prestador_id'), fn($q) => $q->where('prestador_id', $request->prestador_id));

        $dados = $query->get()->groupBy('servico.categoria.nome_categoria')->map(function ($items, $categoria) {
            return [
                'categoria' => $categoria,
                'total' => $items->count(),
                'valor_total' => round($items->sum('preco_acordado'), 2),
                'media_valor' => round($items->avg('preco_acordado'), 2),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $dados,
        ]);
    }

    public function atendimentosPorPrestador(Request $request): JsonResponse
    {
        $query = Agendamento::with('prestador')
            ->when($request->filled('data_inicio'), fn($q) => $q->whereDate('data_hora', '>=', $request->data_inicio))
            ->when($request->filled('data_fim'), fn($q) => $q->whereDate('data_hora', '<=', $request->data_fim));

        $dados = $query->get()->groupBy('prestador_id')->map(function ($items, $prestadorId) {
            $prestador = $items->first()->prestador;
            return [
                'prestador_id' => $prestadorId,
                'prestador_nome' => $prestador->name,
                'total_agendamentos' => $items->count(),
                'concluidos' => $items->where('status', 'concluido')->count(),
                'cancelados' => $items->where('status', 'cancelado')->count(),
                'faturamento_total' => round($items->sum('preco_acordado'), 2),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $dados,
        ]);
    }

    public function financeiro(Request $request): JsonResponse
    {
        $query = Recebimento::with('agendamento:id,data_hora,prestador_id,cliente_id')
            ->when($request->filled('data_inicio'), fn($q) => $q->whereDate('created_at', '>=', $request->data_inicio))
            ->when($request->filled('data_fim'), fn($q) => $q->whereDate('created_at', '<=', $request->data_fim))
            ->when($request->filled('status_recebimento'), fn($q) => $q->where('status_recebimento', $request->status_recebimento));

        $recebimentos = $query->latest()->paginate($request->per_page ?? 20);

        $totais = Recebimento::query()
            ->when($request->filled('data_inicio'), fn($q) => $q->whereDate('created_at', '>=', $request->data_inicio))
            ->when($request->filled('data_fim'), fn($q) => $q->whereDate('created_at', '<=', $request->data_fim))
            ->selectRaw('COALESCE(SUM(valor_total),0) as total_bruto, COALESCE(SUM(taxa_admin),0) as total_taxa, COALESCE(SUM(valor_liquido_prestador),0) as total_liquido')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'resumo' => [
                    'total_bruto' => round($totais->total_bruto, 2),
                    'total_taxa_admin' => round($totais->total_taxa, 2),
                    'total_liquido_prestadores' => round($totais->total_liquido, 2),
                ],
                'recebimentos' => $recebimentos,
            ],
        ]);
    }

    public function auditoria(Request $request): JsonResponse
    {
        $logs = AuditLogService::list($request->only(['acao', 'entidade', 'usuario_id', 'date_from', 'date_to']));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function moderarAvaliacoes(Request $request): JsonResponse
    {
        $query = \App\Models\Avaliacao::with(['cliente', 'prestador', 'agendamento.servico'])
            ->when($request->filled('moderada'), fn($q) => $q->where('moderada', $request->boolean('moderada')))
            ->when($request->filled('nota_min'), fn($q) => $q->where('nota', '>=', $request->nota_min));

        $avaliacoes = $query->latest('data_avaliacao')->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $avaliacoes,
        ]);
    }

    public function moderarAvaliacao(Request $request, \App\Models\Avaliacao $avaliacao): JsonResponse
    {
        $data = $request->validate([
            'moderada' => 'required|boolean',
            'justificativa' => 'required|string|max:500',
        ]);

        \App\Services\AvaliacaoService::moderar(
            $avaliacao,
            $data['moderada'],
            $data['justificativa'],
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Avaliacao moderada com sucesso.',
        ]);
    }

    public function deletarAvaliacao(Request $request, $avaliacaoId): JsonResponse
    {
        // RN9: Apenas admin pode deletar
        $avaliacao = \App\Models\Avaliacao::findOrFail($avaliacaoId);

        $justificativa = $request->validate(['justificativa' => 'required|string|max:500'])['justificativa'];

        AuditLogService::log('avaliacao_deletada_admin', 'avaliacoes', $avaliacao->id, [
            'admin_id' => $request->user()->id,
            'justificativa' => $justificativa,
        ]);

        $avaliacao->delete();

        // Recalcular reputacao do prestador
        \App\Services\AvaliacaoService::atualizarReputacao($avaliacao->prestador_id);

        return response()->json([
            'success' => true,
            'message' => 'Avaliacao removida com sucesso.',
        ]);
    }
}
