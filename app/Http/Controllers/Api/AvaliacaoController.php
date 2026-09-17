<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvaliacaoResource;
use App\Models\Avaliacao;
use App\Services\AvaliacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvaliacaoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Avaliacao::with('cliente')->where('moderada', false);

        if ($request->filled('prestador_id')) {
            $query->where('prestador_id', $request->prestador_id);
        }

        $avaliacoes = $query->latest('data_avaliacao')->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => AvaliacaoResource::collection($avaliacoes)->response()->getData(true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agendamento_id' => 'required|exists:agendamentos,id',
            'nota' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:2000',
        ]);

        $avaliacao = AvaliacaoService::criar(
            $data['agendamento_id'],
            $request->user()->id,
            $data['nota'],
            $data['comentario'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => new AvaliacaoResource($avaliacao),
            'message' => 'Avaliação registrada com sucesso.',
        ], 201);
    }

    public function checkPendentes(Request $request): JsonResponse
    {
        $user = $request->user();

        $pendentes = \App\Models\Agendamento::with('servico')
            ->where('cliente_id', $user->id)
            ->where('status', 'concluido')
            ->whereDoesntHave('avaliacao')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'pendente_avaliacao' => $pendentes->isNotEmpty(),
                'total_pendentes' => $pendentes->count(),
                'agendamentos_pendentes' => $pendentes->map(fn($a) => [
                    'id' => $a->id,
                    'servico_nome' => $a->servico?->nome,
                    'data_hora' => $a->data_hora,
                ]),
            ],
        ]);
    }
}
