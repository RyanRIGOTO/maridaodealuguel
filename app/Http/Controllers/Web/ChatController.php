<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Figura 25 - Tela de uso do Chat (todos os tipos de usuários).
     * Lista de conversas + thread da conversa selecionada (?agendamento=ID).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $agendamentosComChat = Agendamento::where(function ($q) use ($user) {
            $q->where('cliente_id', $user->id)->orWhere('prestador_id', $user->id);
        })->whereHas('chat')->with(['cliente', 'prestador', 'servico'])->get();

        $conversas = $agendamentosComChat->map(function ($agendamento) use ($user) {
            $ultimaMensagem = $agendamento->chat()->latest('data_hora')->first();
            $naoLidas = $agendamento->chat()->where('destinatario_id', $user->id)->where('lido', false)->count();
            $outroUsuario = $agendamento->cliente_id === $user->id ? $agendamento->prestador : $agendamento->cliente;

            return (object) [
                'agendamento_id' => $agendamento->id,
                'outro_usuario' => $outroUsuario,
                'servico_nome' => $agendamento->servico?->nome,
                'ultima_mensagem' => $ultimaMensagem?->mensagem,
                'ultima_mensagem_data' => $ultimaMensagem?->data_hora,
                'nao_lidas' => $naoLidas,
            ];
        })->sortByDesc('ultima_mensagem_data')->values();

        $agendamentoAtivo = null;
        $mensagens = collect();
        $outro = null;

        if ($request->filled('agendamento')) {
            $candidato = Agendamento::with(['cliente', 'prestador'])->find($request->query('agendamento'));

            if ($candidato && ($candidato->cliente_id === $user->id || $candidato->prestador_id === $user->id)) {
                $agendamentoAtivo = $candidato;
                $outro = $candidato->cliente_id === $user->id ? $candidato->prestador : $candidato->cliente;

                Chat::where('agendamento_id', $candidato->id)
                    ->where('destinatario_id', $user->id)
                    ->where('lido', false)
                    ->update(['lido' => true]);

                $mensagens = Chat::where('agendamento_id', $candidato->id)
                    ->orderBy('data_hora')
                    ->get();
            }
        }

        return view('chat.index', compact('conversas', 'agendamentoAtivo', 'mensagens', 'outro'));
    }

    public function send(Request $request, Agendamento $agendamento)
    {
        $user = $request->user();

        if ($agendamento->cliente_id !== $user->id && $agendamento->prestador_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate(['mensagem' => 'required|string|max:2000']);

        $destinatarioId = $agendamento->cliente_id === $user->id
            ? $agendamento->prestador_id
            : $agendamento->cliente_id;

        Chat::create([
            'agendamento_id' => $agendamento->id,
            'remetente_id' => $user->id,
            'destinatario_id' => $destinatarioId,
            'mensagem' => $data['mensagem'],
            'data_hora' => now(),
            'lido' => false,
        ]);

        return redirect()->route('chat.index', ['agendamento' => $agendamento->id]);
    }
}
