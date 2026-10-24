<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Chat;
use App\Models\PropostaHorario;
use App\Services\AgendamentoService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    /**
     * Figura 25 - Tela de uso do Chat (todos os tipos de usuários).
     * Lista de conversas + thread da conversa selecionada (?agendamento=ID).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Não lidas = mensagens da outra parte (o destinatário não é gravado: é quem não enviou).
        $agendamentosComChat = Agendamento::doParticipante($user->id)
            ->whereHas('chat')
            ->with(['cliente', 'prestador', 'servico'])
            ->withCount(['chat as nao_lidas' => fn ($q) => $q->where('remetente_id', '!=', $user->id)->where('lido', false)])
            ->get();

        $conversas = $agendamentosComChat->map(function ($agendamento) use ($user) {
            $ultimaMensagem = $agendamento->chat()->latest('created_at')->latest('id')->first();

            return (object) [
                'agendamento_id' => $agendamento->id,
                'outro_usuario' => $agendamento->cliente_id === $user->id ? $agendamento->prestador : $agendamento->cliente,
                'servico_nome' => $agendamento->servico?->nome,
                'ultima_mensagem' => $ultimaMensagem?->mensagem,
                'ultima_mensagem_data' => $ultimaMensagem?->created_at,
                'nao_lidas' => $agendamento->nao_lidas,
            ];
        })->sortByDesc('ultima_mensagem_data')->values();

        $agendamentoAtivo = null;
        $mensagens = collect();
        $outro = null;

        if ($request->filled('agendamento')) {
            $candidato = Agendamento::with(['cliente', 'prestador', 'servico'])->find($request->query('agendamento'));

            if ($candidato && $candidato->participa($user->id)) {
                $agendamentoAtivo = $candidato;
                $outro = $candidato->cliente_id === $user->id ? $candidato->prestador : $candidato->cliente;

                Chat::where('agendamento_id', $candidato->id)
                    ->where('remetente_id', '!=', $user->id)
                    ->where('lido', false)
                    ->update(['lido' => true]);

                $mensagens = Chat::with('proposta')
                    ->where('agendamento_id', $candidato->id)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->get();
            }
        }

        return view('chat.index', compact('conversas', 'agendamentoAtivo', 'mensagens', 'outro'));
    }

    public function send(Request $request, Agendamento $agendamento)
    {
        $user = $request->user();

        if (!$agendamento->participa($user->id)) {
            abort(403);
        }

        $data = $request->validate(['mensagem' => 'required|string|max:2000']);

        Chat::create([
            'agendamento_id' => $agendamento->id,
            'remetente_id' => $user->id,
            'mensagem' => $data['mensagem'],
        ]);

        return redirect()->route('chat.index', ['agendamento' => $agendamento->id]);
    }

    /**
     * Cliente aceita ou recusa a proposta de novo horário enviada pelo prestador.
     */
    public function responderProposta(Request $request, PropostaHorario $proposta)
    {
        $data = $request->validate(['resposta' => ['required', 'in:aceitar,recusar']]);

        try {
            AgendamentoService::responderProposta($proposta, $request->user()->id, $data['resposta'] === 'aceitar');
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->collapse()->first());
        }

        return redirect()->route('chat.index', ['agendamento' => $proposta->agendamento_id])
            ->with('success', $data['resposta'] === 'aceitar' ? 'Novo horário confirmado.' : 'Proposta recusada.');
    }
}
