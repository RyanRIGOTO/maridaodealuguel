<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Categoria;
use App\Models\Configuracao;
use App\Models\Recebimento;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\AvaliacaoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Figura 21 - Dashboard do Admin.
     */
    public function dashboard()
    {
        // RN10: garante que o alerta apareça mesmo que a rotina diária ainda não tenha rodado.
        AvaliacaoService::verificarRevisoesPendentes();

        $totalClientes = User::where('role', 'cliente')->where('status', 'ativo')->count();
        $totalPrestadores = User::where('role', 'prestador')->where('status', 'ativo')->count();
        $faturamento = Recebimento::sum('valor_total');
        $servicosRealizados = Agendamento::where('status', 'concluido')->count();

        $prestadoresEmRevisao = User::where('role', 'prestador')
            ->whereHas('prestadorProfile', fn ($q) => $q->where('em_revisao', true))
            ->with('prestadorProfile')
            ->get();

        $reclamacoesRecentes = Avaliacao::with(['cliente', 'agendamento.servico.prestador'])
            ->where('nota', '<=', 2)
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalClientes', 'totalPrestadores', 'faturamento', 'servicosRealizados',
            'prestadoresEmRevisao', 'reclamacoesRecentes'
        ));
    }

    /**
     * Figura 22 - Gerenciamento de Usuários (Admin).
     */
    public function usuarios(Request $request)
    {
        $query = User::with('prestadorProfile');

        if ($request->filled('role') && $request->role !== 'todos') {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
        }

        $usuarios = $query->latest()->paginate(15)->withQueryString();

        return view('admin.usuarios', compact('usuarios'));
    }

    /**
     * RN10: o administrador conclui a revisão de um prestador e o libera para novos agendamentos.
     */
    public function concluirRevisao(Request $request, User $user)
    {
        $perfil = $user->prestadorProfile;

        if (!$user->isPrestador() || !$perfil?->em_revisao) {
            return back()->with('error', 'Este prestador não está em revisão.');
        }

        $data = $request->validate(['observacao' => ['nullable', 'string', 'max:500']]);
        AvaliacaoService::concluirRevisao($perfil, $request->user()->id, $data['observacao'] ?? null);

        return back()->with('success', "Revisão de {$user->name} concluída. O prestador voltou a receber agendamentos.");
    }

    public function usuarioStatus(Request $request, User $user)
    {
        // A tela já mostra administradores como "Protegido"; a rota precisa barrar também.
        if ($user->isAdmin()) {
            return back()->with('error', 'Contas de administrador não podem ser inativadas.');
        }

        $novoStatus = $user->status === 'ativo' ? 'inativo' : 'ativo';
        $user->update(['status' => $novoStatus]);

        if ($novoStatus === 'inativo') {
            $user->tokens()->delete();
        }

        AuditLogService::log(
            $novoStatus === 'ativo' ? 'usuario_ativado' : 'usuario_inativado',
            'users',
            $user->id,
            ['admin_id' => $request->user()->id]
        );

        return back()->with('success', 'Usuário '.($novoStatus === 'ativo' ? 'ativado' : 'inativado').' com sucesso.');
    }

    /**
     * Figura 23 - Cadastro de categorias (Admin).
     */
    public function categorias()
    {
        $categorias = Categoria::withCount('servicos')->latest()->get();

        return view('admin.categorias', compact('categorias'));
    }

    public function categoriasStore(Request $request)
    {
        $data = $request->validate([
            'nome_categoria' => 'required|string|max:100|unique:categorias,nome_categoria',
        ]);

        $categoria = Categoria::create([...$data, 'status' => 'ativo']);
        AuditLogService::log('categoria_criada', 'categorias', $categoria->id);

        return back()->with('success', 'Categoria criada com sucesso.');
    }

    public function categoriasUpdate(Request $request, Categoria $categoria)
    {
        $data = $request->validate([
            'nome_categoria' => 'required|string|max:100|unique:categorias,nome_categoria,'.$categoria->id,
            'status' => 'required|in:ativo,inativo',
        ]);

        $categoria->update($data);
        AuditLogService::log('categoria_editada', 'categorias', $categoria->id);

        return back()->with('success', 'Categoria atualizada com sucesso.');
    }

    public function categoriasDestroy(Categoria $categoria)
    {
        // Excluir apagaria em cascata serviços, agendamentos, recebimentos e avaliações.
        if ($categoria->servicos()->exists()) {
            return back()->with('error', 'Esta categoria possui serviços vinculados. Inative-a em vez de excluir, para preservar o histórico.');
        }

        AuditLogService::log('categoria_removida', 'categorias', $categoria->id);
        $categoria->delete();

        return back()->with('success', 'Categoria removida.');
    }

    /**
     * Configurações da plataforma: taxa cobrada sobre cada serviço concluído (RN7).
     */
    public function configuracoes()
    {
        return view('admin.configuracoes', [
            'taxa' => Configuracao::taxaPlataforma(),
            'multa' => Configuracao::multaCancelamento(),
        ]);
    }

    public function taxaUpdate(Request $request)
    {
        $nova = $this->salvarPercentual($request, 'taxa_plataforma', Configuracao::TAXA_PLATAFORMA, Configuracao::taxaPlataforma());

        return back()->with('success', 'Taxa da plataforma atualizada para '.number_format($nova, 2, ',', '.').'%. Ela vale para os serviços concluídos a partir de agora.');
    }

    public function multaUpdate(Request $request)
    {
        $nova = $this->salvarPercentual($request, 'multa_cancelamento', Configuracao::MULTA_CANCELAMENTO, Configuracao::multaCancelamento());

        return back()->with('success', 'Multa de cancelamento atualizada para '.number_format($nova, 2, ',', '.').'%. Ela vale para os cancelamentos feitos a partir de agora.');
    }

    /**
     * Valida um percentual de 0 a 100, grava a configuração e registra na auditoria.
     */
    private function salvarPercentual(Request $request, string $campo, string $chave, float $anterior): float
    {
        $data = $request->validate([
            $campo => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
        ], [
            "{$campo}.required" => 'Informe o percentual.',
            "{$campo}.numeric" => 'O percentual deve ser um número.',
            "{$campo}.decimal" => 'Use no máximo duas casas decimais.',
            "{$campo}.min" => 'O percentual não pode ser negativo.',
            "{$campo}.max" => 'O percentual não pode passar de 100%.',
        ]);

        $nova = round((float) $data[$campo], 2);
        Configuracao::definir($chave, number_format($nova, 2, '.', ''));

        AuditLogService::log("{$chave}_alterada", 'configuracoes', null, [
            'admin_id' => $request->user()->id,
            'valor_anterior' => $anterior,
            'valor_novo' => $nova,
        ]);

        return $nova;
    }

    /**
     * Figura 24 - Menu de Relatórios (Admin).
     */
    public function relatorios()
    {
        $resumo = [
            'Total de Agendamentos' => Agendamento::count(),
            'Serviços Concluídos' => Agendamento::where('status', 'concluido')->count(),
            'Serviços Cancelados' => Agendamento::where('status', 'cancelado')->count(),
            'Faturamento Total' => 'R$ '.number_format(Recebimento::sum('valor_total'), 2, ',', '.'),
        ];

        return view('admin.relatorios.index', [
            'resumo' => $resumo,
            'taxa' => Configuracao::taxaPlataforma(),
        ]);
    }

    /**
     * Figura 28 - Relatório de Agendamentos / Serviços Prestados.
     */
    public function relatorioAgendamentos(Request $request)
    {
        $query = Agendamento::with(['cliente', 'prestador', 'servico']);
        $this->aplicarFiltroPeriodo($query, $request, 'data_hora');
        $agendamentos = $query->orderBy('data_hora')->get();

        return view('admin.relatorios.agendamentos', [
            'agendamentos' => $agendamentos,
            'total' => $agendamentos->count(),
            'valorTotal' => $agendamentos->sum('preco_acordado'),
            'periodo' => $request->only(['data_inicio', 'data_fim']),
        ]);
    }

    /**
     * Figura 29 - Relatório Financeiro da Plataforma.
     */
    public function relatorioFinanceiro(Request $request)
    {
        $query = Recebimento::with(['agendamento.servico', 'agendamento.prestador', 'agendamento.cliente']);
        $this->aplicarFiltroPeriodo($query, $request, 'created_at');
        $recebimentos = $query->orderBy('created_at')->get();

        return view('admin.relatorios.financeiro', [
            'recebimentos' => $recebimentos,
            'totalBruto' => $recebimentos->sum('valor_total'),
            'totalTaxa' => $recebimentos->sum('taxa_admin'),
            'totalLiquido' => $recebimentos->sum('valor_liquido_prestador'),
            'periodo' => $request->only(['data_inicio', 'data_fim']),
        ]);
    }

    /**
     * Figura 30 - Relatório de Avaliação dos Prestadores.
     */
    public function relatorioAvaliacoes(Request $request)
    {
        $query = Avaliacao::with(['cliente', 'agendamento.servico.prestador']);
        $this->aplicarFiltroPeriodo($query, $request, 'created_at');
        $avaliacoes = $query->orderBy('created_at')->get();

        $porPrestador = $avaliacoes->groupBy('prestador_id')->map(fn ($items) => [
            'prestador' => $items->first()->prestador,
            'media' => round($items->avg('nota'), 2),
            'total' => $items->count(),
        ])->sortByDesc('media')->values();

        return view('admin.relatorios.avaliacoes', [
            'avaliacoes' => $avaliacoes,
            'porPrestador' => $porPrestador,
            'melhor' => $porPrestador->first(),
            'pior' => $porPrestador->sortBy('media')->first(),
            'periodo' => $request->only(['data_inicio', 'data_fim']),
        ]);
    }

    /**
     * Figura 32 - Relatório de Usuários Cadastrados.
     */
    public function relatorioUsuarios(Request $request)
    {
        $query = User::query();
        $this->aplicarFiltroPeriodo($query, $request, 'created_at');
        $usuarios = $query->orderBy('created_at')->get();

        return view('admin.relatorios.usuarios', [
            'usuarios' => $usuarios,
            'totalClientes' => $usuarios->where('role', 'cliente')->count(),
            'totalPrestadores' => $usuarios->where('role', 'prestador')->count(),
            'totalAdmins' => $usuarios->where('role', 'admin')->count(),
            'periodo' => $request->only(['data_inicio', 'data_fim']),
        ]);
    }

    private function aplicarFiltroPeriodo(Builder $query, Request $request, string $coluna): void
    {
        $periodo = $request->validate([
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
        ]);

        if (!empty($periodo['data_inicio'])) {
            $query->whereDate($coluna, '>=', $periodo['data_inicio']);
        }
        if (!empty($periodo['data_fim'])) {
            $query->whereDate($coluna, '<=', $periodo['data_fim']);
        }
    }
}
