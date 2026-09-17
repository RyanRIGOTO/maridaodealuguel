<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Categoria;
use App\Models\Recebimento;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Figura 21 - Dashboard do Admin.
     */
    public function dashboard()
    {
        $totalClientes = User::where('role', 'cliente')->where('status', 'ativo')->count();
        $totalPrestadores = User::where('role', 'prestador')->where('status', 'ativo')->count();
        $faturamento = Recebimento::sum('valor_total');
        $servicosRealizados = Agendamento::where('status', 'concluido')->count();

        $prestadoresEmRevisao = User::where('role', 'prestador')
            ->whereHas('prestadorProfile', fn ($q) => $q->where('em_revisao', true))
            ->with('prestadorProfile')
            ->get();

        $reclamacoesRecentes = Avaliacao::with(['cliente', 'prestador'])
            ->where('nota', '<=', 2)
            ->latest('data_avaliacao')
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
        $query = User::with(['clienteProfile', 'prestadorProfile']);

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

    public function usuarioStatus(Request $request, User $user)
    {
        $novoStatus = $user->status === 'ativo' ? 'inativo' : 'ativo';
        $user->update(['status' => $novoStatus]);

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
        AuditLogService::log('categoria_removida', 'categorias', $categoria->id);
        $categoria->delete();

        return back()->with('success', 'Categoria removida.');
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

        return view('admin.relatorios.index', compact('resumo'));
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
        $query = Avaliacao::with(['cliente', 'prestador']);
        $this->aplicarFiltroPeriodo($query, $request, 'data_avaliacao');
        $avaliacoes = $query->orderBy('data_avaliacao')->get();

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
        if ($request->filled('data_inicio')) {
            $query->whereDate($coluna, '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate($coluna, '<=', $request->data_fim);
        }
    }
}
