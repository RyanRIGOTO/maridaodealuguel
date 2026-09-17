<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Categoria;
use App\Services\AgendamentoService;
use App\Services\AvaliacaoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClienteController extends Controller
{
    /**
     * Figura 9 - Dashboard do Cliente.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $servicosContratados = Agendamento::where('cliente_id', $user->id)->count();
        $agendamentosPendentes = Agendamento::where('cliente_id', $user->id)->where('status', 'pendente')->count();
        $avaliacoesFaltando = Agendamento::where('cliente_id', $user->id)
            ->where('status', 'concluido')
            ->whereDoesntHave('avaliacao')
            ->count();

        $servicosPopulares = Categoria::withCount('servicos')
            ->where('status', 'ativo')
            ->orderByDesc('servicos_count')
            ->take(4)
            ->get();

        $agendamentosRecentes = Agendamento::with(['servico', 'prestador'])
            ->where('cliente_id', $user->id)
            ->latest('data_hora')
            ->take(5)
            ->get();

        return view('cliente.dashboard', compact(
            'servicosContratados', 'agendamentosPendentes', 'avaliacoesFaltando',
            'servicosPopulares', 'agendamentosRecentes'
        ));
    }

    /**
     * Figuras 10 a 13 - Agendamento do Cliente (assistente em 4 fases).
     */
    public function agendar()
    {
        $categorias = Categoria::where('status', 'ativo')
            ->with(['servicos' => function ($q) {
                $q->where('status', 'ativo')
                    ->whereHas('prestador', fn ($qq) => $qq->where('status', 'ativo'))
                    ->with('prestador.prestadorProfile');
            }])
            ->orderBy('nome_categoria')
            ->get()
            ->filter(fn ($categoria) => $categoria->servicos->isNotEmpty())
            ->values();

        // Estrutura enxuta (sem dados sensíveis do prestador) para alimentar o
        // assistente de 4 fases no front-end (Alpine.js), evitando expor
        // e-mail/telefone/CPF do prestador no HTML da página.
        $dadosWizard = $categorias->map(fn ($categoria) => [
            'id' => $categoria->id,
            'nome' => $categoria->nome_categoria,
            'servicos' => $categoria->servicos->map(fn ($servico) => [
                'id' => $servico->id,
                'nome' => $servico->nome,
                'descricao' => $servico->descricao,
                'preco' => (float) $servico->preco_sugerido,
                'prestador' => [
                    'id' => $servico->prestador->id,
                    'nome' => $servico->prestador->name,
                    'area_atuacao' => optional($servico->prestador->prestadorProfile)->area_atuacao,
                    'reputacao' => (float) (optional($servico->prestador->prestadorProfile)->reputacao_media ?? 5),
                ],
            ])->values(),
        ])->values();

        return view('cliente.agendar', compact('categorias', 'dadosWizard'));
    }

    public function agendarStore(Request $request)
    {
        $data = $request->validate([
            'servico_id' => 'required|exists:servicos,id',
            'data' => 'required|date|after_or_equal:today',
            'hora' => 'required',
            'endereco_servico' => 'required|string|max:255',
        ]);

        try {
            $dataHora = Carbon::parse($data['data'].' '.$data['hora']);

            AgendamentoService::criar(
                $request->user()->id,
                (int) $data['servico_id'],
                $dataHora,
                $data['endereco_servico'],
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('cliente.historico')
            ->with('success', 'Agendamento realizado com sucesso! Aguardando confirmação do prestador.');
    }

    /**
     * Figura 14 - Histórico de Agendamentos (Cliente).
     */
    public function historico(Request $request)
    {
        $query = Agendamento::with(['servico.categoria', 'prestador.prestadorProfile', 'avaliacao'])
            ->where('cliente_id', $request->user()->id);

        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }

        $agendamentos = $query->latest('data_hora')->paginate(8)->withQueryString();

        return view('cliente.historico', compact('agendamentos'));
    }

    public function cancelar(Request $request, Agendamento $agendamento)
    {
        if ($agendamento->cliente_id !== $request->user()->id) {
            abort(403);
        }

        try {
            AgendamentoService::cancelar($agendamento, $request->user()->id);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->collapse()->first());
        }

        return back()->with('success', 'Agendamento cancelado.');
    }

    /**
     * Figura 15 - Tela de Avaliações (Cliente).
     */
    public function avaliacoes(Request $request)
    {
        $user = $request->user();

        $pendentes = Agendamento::with(['servico', 'prestador'])
            ->where('cliente_id', $user->id)
            ->where('status', 'concluido')
            ->whereDoesntHave('avaliacao')
            ->latest('data_hora')
            ->get();

        $avaliadas = Agendamento::with(['servico', 'prestador', 'avaliacao'])
            ->where('cliente_id', $user->id)
            ->whereHas('avaliacao')
            ->latest('data_hora')
            ->get();

        return view('cliente.avaliacoes', compact('pendentes', 'avaliadas'));
    }

    public function avaliar(Request $request, Agendamento $agendamento)
    {
        $data = $request->validate([
            'nota' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:2000',
        ]);

        try {
            AvaliacaoService::criar(
                $agendamento->id,
                $request->user()->id,
                (int) $data['nota'],
                $data['comentario'] ?? null,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Avaliação registrada. Obrigado pelo feedback!');
    }
}
