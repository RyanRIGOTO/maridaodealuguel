<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServicoResource;
use App\Models\Servico;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServicoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Servico::with(['categoria', 'prestador.prestadorProfile'])
            ->where('status', 'ativo')
            ->whereHas('prestador', fn($q) => $q->where('status', 'ativo'));

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('search')) {
            $termo = $request->search;
            $query->where(function ($q) use ($termo) {
                $q->where('nome', 'like', "%{$termo}%")
                  ->orWhere('descricao', 'like', "%{$termo}%");
            });
        }

        $servicos = $query->latest()->paginate($request->per_page ?? 12);

        return response()->json([
            'success' => true,
            'data' => ServicoResource::collection($servicos)->response()->getData(true),
        ]);
    }

    public function meusServicos(Request $request): JsonResponse
    {
        $servicos = Servico::with('categoria')
            ->where('prestador_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => ServicoResource::collection($servicos)->response()->getData(true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nome' => 'required|string|max:100',
            'descricao' => 'required|string|max:2000',
            'preco_sugerido' => 'required|numeric|min:0|max:99999.99',
        ]);

        $servico = Servico::create([
            ...$data,
            'prestador_id' => $request->user()->id,
            'status' => 'ativo',
        ]);

        AuditLogService::log('servico_criado', 'servicos', $servico->id);

        return response()->json([
            'success' => true,
            'data' => new ServicoResource($servico->load(['categoria', 'prestador'])),
            'message' => 'Serviço cadastrado com sucesso.',
        ], 201);
    }

    public function update(Request $request, Servico $servico): JsonResponse
    {
        if ($servico->prestador_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Acesso negado.'],
            ], 403);
        }

        $data = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nome' => 'required|string|max:100',
            'descricao' => 'required|string|max:2000',
            'preco_sugerido' => 'required|numeric|min:0|max:99999.99',
            'status' => 'required|in:ativo,inativo',
        ]);

        $servico->update($data);
        AuditLogService::log('servico_editado', 'servicos', $servico->id);

        return response()->json([
            'success' => true,
            'data' => new ServicoResource($servico->load(['categoria', 'prestador'])),
            'message' => 'Serviço atualizado com sucesso.',
        ]);
    }

    public function destroy(Servico $servico): JsonResponse
    {
        if ($servico->prestador_id !== request()->user()->id) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Você não pode excluir este serviço.'],
            ], 403);
        }

        AuditLogService::log('servico_removido', 'servicos', $servico->id);
        $servico->delete();

        return response()->json([
            'success' => true,
            'message' => 'Serviço removido com sucesso.',
        ]);
    }
}
