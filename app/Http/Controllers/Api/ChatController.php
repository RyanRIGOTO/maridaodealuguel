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
        if ($agendamento->cliente_id !== $user->id && $agendamento->prestador_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Acesso negado.'],
            ], 403);
        }

        // Marca mensagens nao lidas como lidas
        Chat::where('agendamento_id', $agendamento->id)
            ->where('destinatario_id', $user->id)
            ->where('lido', false)
            ->update(['lido' => true]);

        $mensagens = Chat::with(['remetente', 'destinatario'])
            ->where('agendamento_id', $agendamento->id)
            ->orderBy('data_hora', 'asc')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => ChatResource::collection($mensagens)->response()->getData(true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agendamento_id' => 'required|exists:agendamentos,id',
            'destinatario_id' => 'required|exists:users,id',
            'mensagem' => 'required|string|max:2000',
        ]);

        $agendamento = \App\Models\Agendamento::findOrFail($data['agendamento_id']);
        $user = $request->user();

        if ($agendamento->cliente_id !== $user->id && $agendamento->prestador_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Voce nao faz parte deste agendamento.'],
            ], 403);
        }

        $chat = Chat::create([
            'agendamento_id' => $data['agendamento_id'],
            'remetente_id' => $user->id,
            'destinatario_id' => $data['destinatario_id'],
            'mensagem' => $data['mensagem'],
            'data_hora' => now(),
            'lido' => false,
        ]);

        return response()->json([
            'success' => true,
            'data' => new ChatResource($chat->load(['remetente', 'destinatario'])),
            'message' => 'Mensagem enviada.',
        ], 201);
    }

    public function listarConversas(Request $request): JsonResponse
    {
        $user = $request->user();

        $agendamentos = Agendamento::where(function ($q) use ($user) {
            $q->where('cliente_id', $user->id)->orWhere('prestador_id', $user->id);
        })->whereHas('chat')->with(['cliente', 'prestador', 'servico'])->latest('updated_at')->get();

        $conversas = $agendamentos->map(function ($agendamento) use ($user) {
            $ultimaMensagem = $agendamento->chat()->latest('data_hora')->first();
            $naoLidas = $agendamento->chat()
                ->where('destinatario_id', $user->id)
                ->where('lido', false)
                ->count();

            return [
                'agendamento_id' => $agendamento->id,
                'outro_usuario' => $agendamento->cliente_id === $user->id
                    ? ['id' => $agendamento->prestador->id, 'name' => $agendamento->prestador->name]
                    : ['id' => $agendamento->cliente->id, 'name' => $agendamento->cliente->name],
                'servico_nome' => $agendamento->servico?->nome,
                'ultima_mensagem' => $ultimaMensagem?->mensagem,
                'ultima_mensagem_data' => $ultimaMensagem?->data_hora,
                'nao_lidas' => $naoLidas,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $conversas,
        ]);
    }
}
