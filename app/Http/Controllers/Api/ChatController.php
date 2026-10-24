<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatResource;
use App\Models\Agendamento;
use App\Models\Chat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request, Agendamento $agendamento): JsonResponse
    {
        $user = $request->user();

        // Verifica se usuario esta envolvido no agendamento
        if (!$agendamento->participa($user->id)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Acesso negado.'],
            ], 403);
        }

        // Marca como lidas as mensagens da outra parte
        Chat::where('agendamento_id', $agendamento->id)
            ->where('remetente_id', '!=', $user->id)
            ->where('lido', false)
            ->update(['lido' => true]);

        $mensagens = Chat::with(['remetente', 'proposta', 'agendamento.servico'])
            ->where('agendamento_id', $agendamento->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate($this->porPagina($request, 50));

        return response()->json([
            'success' => true,
            'data' => ChatResource::collection($mensagens)->response()->getData(true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agendamento_id' => 'required|exists:agendamentos,id',
            'mensagem' => 'required|string|max:2000',
        ]);

        $agendamento = Agendamento::findOrFail($data['agendamento_id']);
        $user = $request->user();

        if (!$agendamento->participa($user->id)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Voce nao faz parte deste agendamento.'],
            ], 403);
        }

        // O destinatário é sempre a outra parte do agendamento (não é informado pelo cliente da API).
        $chat = Chat::create([
            'agendamento_id' => $agendamento->id,
            'remetente_id' => $user->id,
            'mensagem' => $data['mensagem'],
        ]);

        return response()->json([
            'success' => true,
            'data' => new ChatResource($chat->load(['remetente', 'agendamento.servico'])),
            'message' => 'Mensagem enviada.',
        ], 201);
    }

    public function listarConversas(Request $request): JsonResponse
    {
        $user = $request->user();

        $agendamentos = Agendamento::doParticipante($user->id)
            ->whereHas('chat')
            ->with(['cliente', 'prestador', 'servico'])
            ->withCount(['chat as nao_lidas' => fn ($q) => $q->where('remetente_id', '!=', $user->id)->where('lido', false)])
            ->latest('updated_at')
            ->get();

        $conversas = $agendamentos->map(function ($agendamento) use ($user) {
            $ultimaMensagem = $agendamento->chat()->latest('created_at')->latest('id')->first();
            $outro = $agendamento->cliente_id === $user->id ? $agendamento->prestador : $agendamento->cliente;

            return [
                'agendamento_id' => $agendamento->id,
                'outro_usuario' => ['id' => $outro->id, 'name' => $outro->name],
                'servico_nome' => $agendamento->servico?->nome,
                'ultima_mensagem' => $ultimaMensagem?->mensagem,
                'ultima_mensagem_data' => $ultimaMensagem?->created_at,
                'nao_lidas' => $agendamento->nao_lidas,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $conversas,
        ]);
    }
}
