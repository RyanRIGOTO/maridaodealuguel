<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgendamentoResource;
use App\Models\Agendamento;
use App\Services\AgendamentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendamentoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Agendamento::with(['cliente', 'prestador', 'servico.categoria', 'avaliacao']);

        if ($user->isCliente()) {
            $query->where('cliente_id', $user->id);
        } elseif ($user->isPrestador()) {
            $query->where('prestador_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $agendamentos = $query->latest('data_hora')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => AgendamentoResource::collection($agendamentos)->response()->getData(true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'servico_id' => 'required|exists:servicos,id',
            'data_hora' => 'required|date|after:now',
            'endereco_servico' => 'required|string|max:255',
        ]);

        $agendamento = AgendamentoService::criar(
            $request->user()->id,
            $data['servico_id'],
            \Carbon\Carbon::parse($data['data_hora']),
            $data['endereco_servico'],
        );

        return response()->json([
            'success' => true,
            'data' => new AgendamentoResource($agendamento->load(['cliente', 'prestador', 'servico.categoria'])),
            'message' => 'Agendamento criado com sucesso.',
        ], 201);
    }

    public function confirmar(Agendamento $agendamento): JsonResponse
    {
        $agendamento = AgendamentoService::confirmar($agendamento, request()->user()->id);

        return response()->json([
            'success' => true,
            'data' => new AgendamentoResource($agendamento),
            'message' => 'Agendamento confirmado com sucesso.',
        ]);
    }

    public function concluir(Agendamento $agendamento): JsonResponse
    {
        $agendamento = AgendamentoService::concluir($agendamento, request()->user()->id);

        return response()->json([
            'success' => true,
            'data' => new AgendamentoResource($agendamento),
            'message' => 'Serviço concluído com sucesso.',
        ]);
    }

    public function cancelar(Agendamento $agendamento): JsonResponse
    {
        $agendamento = AgendamentoService::cancelar($agendamento, request()->user()->id);

        return response()->json([
            'success' => true,
            'data' => new AgendamentoResource($agendamento),
            'message' => 'Agendamento cancelado com sucesso.',
        ]);
    }

    public function verificarConflito(Request $request): JsonResponse
    {
        $request->validate([
            'prestador_id' => 'required|exists:users,id',
            'data_hora' => 'required|datetime',
        ]);

        try {
            AgendamentoService::verificarConflito(
                (int)$request->prestador_id,
                $request->user()->id,
                \Carbon\Carbon::parse($request->data_hora),
            );

            return response()->json([
                'success' => true,
                'data' => ['conflito' => false],
                'message' => 'Horário disponível.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => true,
                'data' => ['conflito' => true],
                'message' => 'Horário indisponível.',
            ]);
        }
    }
}
