<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Categoria;
use App\Models\Recebimento;
use App\Models\Servico;
use App\Services\AgendamentoService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PrestadorController extends Controller
{
    /**
     * Figura 16 - Dashboard do Prestador.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $faturamentoMes = Recebimento::whereHas('agendamento', fn ($q) => $q->where('prestador_id', $user->id))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('valor_liquido_prestador');

        $totalAgendamentos = Agendamento::where('prestador_id', $user->id)->count();
        $servicosConcluidos = Agendamento::where('prestador_id', $user->id)->where('status', 'concluido')->count();
        $avaliacaoMedia = optional($user->prestadorProfile)->reputacao_media ?? 5.00;

        $proximosAgendamentos = Agendamento::with(['cliente', 'servico'])
            ->where('prestador_id', $user->id)
            ->whereIn('status', ['pendente', 'confirmado'])
            ->orderBy('data_hora')
            ->take(5)
            ->get();

        return view('prestador.dashboard', compact(
            'faturamentoMes', 'totalAgendamentos', 'servicosConcluidos', 'avaliacaoMedia', 'proximosAgendamentos'
        ));
    }

    /**
     * Figura 17 - Tela de agendamentos (Prestador).
     */
    public function agendamentos(Request $request)
    {
        $query = Agendamento::with(['cliente', 'servico'])->where('prestador_id', $request->user()->id);

        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }

        $agendamentos = $query->orderBy('data_hora')->paginate(10)->withQueryString();

        return view('prestador.agendamentos', compact('agendamentos'));
    }

    public function confirmar(Request $request, Agendamento $agendamento)
    {
        try {
            AgendamentoService::confirmar($agendamento, $request->user()->id);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->collapse()->first());
        }

        return back()->with('success', 'Agendamento confirmado.');
    }

    public function concluir(Request $request, Agendamento $agendamento)
    {
        try {
            AgendamentoService::concluir($agendamento, $request->user()->id);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->collapse()->first());
        }

        return back()->with('success', 'Serviço concluído! O pagamento será liberado em até 48h (menos a taxa da plataforma).');
    }

    public function cancelar(Request $request, Agendamento $agendamento)
    {
        if ($agendamento->prestador_id !== $request->user()->id) {
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
     * Figuras 18 e 19 - Meus Serviços / Cadastro de Serviço (Prestador).
     */
    public function servicos(Request $request)
    {
        $servicos = Servico::with('categoria')
            ->where('prestador_id', $request->user()->id)
            ->latest()
            ->get();

        $categorias = Categoria::where('status', 'ativo')->orderBy('nome_categoria')->get();

        return view('prestador.servicos', compact('servicos', 'categorias'));
    }

    public function servicosStore(Request $request)
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

        return back()->with('success', 'Serviço cadastrado com sucesso.');
    }

    public function servicosUpdate(Request $request, Servico $servico)
    {
        if ($servico->prestador_id !== $request->user()->id) {
            abort(403);
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

        return back()->with('success', 'Serviço atualizado com sucesso.');
    }

    public function servicosDestroy(Request $request, Servico $servico)
    {
        if ($servico->prestador_id !== $request->user()->id) {
            abort(403);
        }

        AuditLogService::log('servico_removido', 'servicos', $servico->id);
        $servico->delete();

        return back()->with('success', 'Serviço removido.');
    }

    /**
     * Figura 20 - Histórico de Agendamentos (Prestador).
     */
    public function historico(Request $request)
    {
        $query = Agendamento::with(['cliente', 'servico', 'avaliacao'])
            ->where('prestador_id', $request->user()->id)
            ->whereIn('status', ['concluido', 'cancelado']);

        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }

        $agendamentos = $query->latest('data_hora')->paginate(8)->withQueryString();

        return view('prestador.historico', compact('agendamentos'));
    }

    /**
     * Relatório individual dos serviços do prestador, com atendimentos,
     * repasses e avaliações no período selecionado.
     */
    public function relatorioServicos(Request $request)
    {
        $periodo = $request->validate([
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
        ]);
        $prestadorId = $request->user()->id;

        $agendamentosQuery = Agendamento::with(['servico', 'avaliacao', 'recebimento'])
            ->where('prestador_id', $prestadorId);

        if (!empty($periodo['data_inicio'])) {
            $agendamentosQuery->whereDate('data_hora', '>=', $periodo['data_inicio']);
        }
        if (!empty($periodo['data_fim'])) {
            $agendamentosQuery->whereDate('data_hora', '<=', $periodo['data_fim']);
        }

        $agendamentos = $agendamentosQuery->get();
        $servicos = Servico::with('categoria')
            ->where('prestador_id', $prestadorId)
            ->orderBy('nome')
            ->get();

        $porServico = $servicos->map(function (Servico $servico) use ($agendamentos) {
            $atendimentos = $agendamentos->where('servico_id', $servico->id);
            $recebimentos = $atendimentos->pluck('recebimento')->filter();
            $avaliacoes = $atendimentos->pluck('avaliacao')
                ->filter(fn ($avaliacao) => $avaliacao && !$avaliacao->moderada);

            return (object) [
                'servico' => $servico,
                'agendamentos' => $atendimentos->count(),
                'concluidos' => $atendimentos->where('status', 'concluido')->count(),
                'cancelados' => $atendimentos->where('status', 'cancelado')->count(),
                'valor_bruto' => (float) $recebimentos->sum('valor_total'),
                'valor_liquido' => (float) $recebimentos->sum('valor_liquido_prestador'),
                'avaliacoes' => $avaliacoes->count(),
                'media_avaliacoes' => $avaliacoes->isNotEmpty() ? round($avaliacoes->avg('nota'), 1) : null,
            ];
        });

        $avaliacoes = $agendamentos->pluck('avaliacao')
            ->filter(fn ($avaliacao) => $avaliacao && !$avaliacao->moderada);

        $resumo = [
            'servicos' => $servicos->count(),
            'concluidos' => $agendamentos->where('status', 'concluido')->count(),
            'valor_liquido' => $porServico->sum('valor_liquido'),
            'media_avaliacoes' => $avaliacoes->isNotEmpty() ? round($avaliacoes->avg('nota'), 1) : null,
        ];

        return view('prestador.relatorios.servicos', compact('periodo', 'porServico', 'resumo'));
    }
}
