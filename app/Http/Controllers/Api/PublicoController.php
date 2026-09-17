<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvaliacaoResource;
use App\Http\Resources\ServicoResource;
use App\Http\Resources\UserResource;
use App\Models\Avaliacao;
use App\Models\Categoria;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicoController extends Controller
{
    /**
     * Lista prestadores ativos, com filtro por categoria e área de atuação.
     * Requisito TCC: "filtros de busca de profissionais por categorias e áreas de atuação"
     */
    public function prestadores(Request $request): JsonResponse
    {
        $query = User::where('role', 'prestador')
            ->where('status', 'ativo')
            ->with(['prestadorProfile', 'servicos' => fn($q) => $q->where('status', 'ativo')]);

        if ($request->filled('categoria_id')) {
            $query->whereHas('servicos', fn($q) => $q->where('categoria_id', $request->categoria_id));
        }

        if ($request->filled('area')) {
            $query->whereHas('prestadorProfile', fn($q) => $q->where('area_atuacao', 'like', "%{$request->area}%"));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $prestadores = $query->latest()->paginate($request->per_page ?? 12);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($prestadores)->response()->getData(true),
        ]);
    }

    /**
     * Perfil público de um prestador: dados, serviços ativos, avaliações.
     */
    public function prestadorShow(User $user): JsonResponse
    {
        if (!$user->isPrestador() || !$user->isActive()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Prestador não encontrado.'],
            ], 404);
        }

        $user->load(['prestadorProfile', 'servicos' => fn($q) => $q->where('status', 'ativo')->with('categoria')]);

        $avaliacoes = Avaliacao::with('cliente')
            ->where('prestador_id', $user->id)
            ->where('moderada', false)
            ->latest('data_avaliacao')
            ->limit(10)
            ->get();

        $mediaNotas = Avaliacao::where('prestador_id', $user->id)
            ->where('moderada', false)
            ->avg('nota');

        $totalAvaliacoes = Avaliacao::where('prestador_id', $user->id)
            ->where('moderada', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'prestador' => new UserResource($user),
                'servicos' => ServicoResource::collection($user->servicos),
                'avaliacoes' => AvaliacaoResource::collection($avaliacoes),
                'estatisticas' => [
                    'media_notas' => round($mediaNotas ?? 0, 2),
                    'total_avaliacoes' => $totalAvaliacoes,
                ],
            ],
        ]);
    }

    /**
     * Lista serviços de um prestador específico (público).
     */
    public function servicosPorPrestador(User $user): JsonResponse
    {
        if (!$user->isPrestador()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Prestador não encontrado.'],
            ], 404);
        }

        $servicos = Servico::with('categoria')
            ->where('prestador_id', $user->id)
            ->where('status', 'ativo')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => ServicoResource::collection($servicos),
        ]);
    }

    /**
     * Detalhes de um serviço específico (público).
     */
    public function servicoShow(Servico $servico): JsonResponse
    {
        $servico->load(['categoria', 'prestador.prestadorProfile']);

        return response()->json([
            'success' => true,
            'data' => new ServicoResource($servico),
        ]);
    }

    /**
     * Lista todas as avaliações públicas (de serviços concluídos).
     * Requisito TCC: "reputação/avaliações"
     */
    public function avaliacoesPublicas(Request $request): JsonResponse
    {
        $query = Avaliacao::with(['cliente', 'prestador', 'agendamento.servico'])
            ->where('moderada', false)
            ->whereHas('agendamento', fn($q) => $q->where('status', 'concluido'));

        if ($request->filled('prestador_id')) {
            $query->where('prestador_id', $request->prestador_id);
        }

        if ($request->filled('nota_min')) {
            $query->where('nota', '>=', $request->nota_min);
        }

        $avaliacoes = $query->latest('data_avaliacao')->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => AvaliacaoResource::collection($avaliacoes)->response()->getData(true),
        ]);
    }
}
