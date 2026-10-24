<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgendamentoResource;
use App\Models\Agendamento;
use App\Services\AgendamentoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AgendamentoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Agendamento::with(['cliente', 'prestador', 'servico.categoria', 'avaliacao', 'endereco', 'cancelamento']);

        if ($user->isCliente()) {
            $query->where('cliente_id', $user->id);
        } elseif ($user->isPrestador()) {
            $query->doPrestador($user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $agendamentos = $query->latest('data_hora')->paginate($this->porPagina($request, 15));

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
            // Um dos endereços do próprio cliente (GET /api/auth/me traz o principal).
            'endereco_id' => ['required', Rule::exists('enderecos', 'id')->where('user_id', $request->user()->id)],
        ]);

        $agendamento = AgendamentoService::criar(
            $request->user()->id,
            (int) $data['servico_id'],
            Carbon::parse($data['data_hora']),
            (int) $data['endereco_id'],
        );

        return response()->json([
            'success' => true,
            'data' => new AgendamentoResource($agendamento->load(['cliente', 'prestador', 'servico.categoria', 'endereco'])),
            'message' => 'Agendamento criado com sucesso.',
        ], 201);
    }

    public function confirmar(Request $request, Agendamento $agendamento): JsonResponse
    {
        $agendamento = AgendamentoService::confirmar($agendamento, $request->user()->id);

        return response()->json([
            'success' => true,
            'data' => new AgendamentoResource($agendamento),
            'message' => 'Agendamento confirmado com sucesso.',
        ]);
    }

    public function concluir(Request $request, Agendamento $agendamento): JsonResponse
    {
        $agendamento = AgendamentoService::concluir($agendamento, $request->user()->id);

        return response()->json([
            'success' => true,
            'data' => new AgendamentoResource($agendamento),
            'message' => 'Serviço concluído com sucesso.',
        ]);
    }

    public function cancelar(Request $request, Agendamento $agendamento): JsonResponse
    {
        $agendamento = AgendamentoService::cancelar($agendamento, $request->user()->id);

        return response()->json([
            'success' => true,
            'data' => new AgendamentoResource($agendamento),
            'message' => 'Agendamento cancelado com sucesso.',
        ]);
    }

    public function verificarConflito(Request $request): JsonResponse
    {
        $request->validate([
            'prestador_id' => ['required', Rule::exists('users', 'id')->where('role', 'prestador')],
            'data_hora' => 'required|date',
        ]);

        try {
            AgendamentoService::verificarConflito(
                (int)$request->prestador_id,
                $request->user()->id,
                Carbon::parse($request->data_hora),
            );

            return response()->json([
                'success' => true,
                'data' => ['conflito' => false],
                'message' => 'Horário disponível.',
            ]);
        } catch (ValidationException) {
            return response()->json([
                'success' => true,
                'data' => ['conflito' => true],
                'message' => 'Horário indisponível.',
            ]);
        }
    }
}
