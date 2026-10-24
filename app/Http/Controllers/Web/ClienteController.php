<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Categoria;
use App\Models\Configuracao;
use App\Models\Pagamento;
use App\Services\AgendamentoService;
use App\Http\Requests\AgendarRequest;
use App\Services\AvaliacaoService;
use App\Services\CadastroService;
use Illuminate\Support\Facades\DB;
use App\Services\PagamentoService;
use Illuminate\Validation\Rule;
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
    public function agendar(Request $request)
    {
        // Todas as categorias ativas aparecem, mesmo sem serviço no momento;
        // a tela avisa quando a categoria escolhida não tem serviço disponível.
        $categorias = Categoria::where('status', 'ativo')
            ->with(['servicos' => fn ($q) => $q->contratavel()->with('prestador.prestadorProfile')])
            ->orderBy('nome_categoria')
            ->get();

        // Reputação de todos os prestadores da tela numa consulta só.
        $prestadorIds = $categorias->pluck('servicos')->flatten()->pluck('prestador_id')->unique()->values()->all();

        return view('cliente.agendar', [
            'categorias' => $categorias,
            'reputacoes' => AvaliacaoService::medias($prestadorIds),
            'multa' => Configuracao::multaCancelamento(),
            'enderecoCadastro' => $request->user()->enderecoPrincipal,
        ]);
    }

    public function agendarStore(AgendarRequest $request)
    {
        $data = $request->validated();
        $cliente = $request->user();

        try {
            // O endereço novo só fica salvo se o agendamento for criado.
            DB::transaction(function () use ($data, $cliente) {
                $enderecoId = $data['endereco_opcao'] === 'outro'
                    // Reaproveita o endereço se o cliente já tiver digitado o mesmo antes.
                    ? $cliente->enderecos()->firstOrCreate(CadastroService::camposEndereco($data))->id
                    : (int) $data['endereco_id'];

                AgendamentoService::criar(
                    $cliente->id,
                    (int) $data['servico_id'],
                    Carbon::createFromFormat('Y-m-d H:i', $data['data'].' '.$data['hora']),
                    $enderecoId,
                );
            });
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
        $query = Agendamento::with(['servico.categoria', 'prestador.prestadorProfile', 'avaliacao', 'cancelamento'])
            ->where('cliente_id', $request->user()->id);

        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }

        // Busca pelo nome do serviço ou do prestador.
        if ($request->filled('busca')) {
            $termo = '%'.addcslashes($request->string('busca')->trim(), '%_\\').'%';
            $query->where(fn ($q) => $q
                ->whereHas('servico', fn ($qq) => $qq->where('nome', 'like', $termo))
                ->orWhereHas('prestador', fn ($qq) => $qq->where('name', 'like', $termo)));
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

    /**
     * RN4: tela de pagamento da multa de cancelamento.
     */
    public function pagarMulta(Request $request, Agendamento $agendamento)
    {
        if (!PagamentoService::multasPendentes($request->user()->id)->whereKey($agendamento->id)->exists()) {
            return redirect()->route('cliente.historico')->with('error', 'Não há multa pendente para este agendamento.');
        }

        return view('cliente.pagar-multa', [
            'agendamento' => $agendamento->load(['servico', 'prestador', 'cancelamento']),
            'metodos' => Pagamento::METODOS,
        ]);
    }

    public function pagarMultaStore(Request $request, Agendamento $agendamento, PagamentoService $pagamentos)
    {
        $data = $request->validate([
            'metodo' => ['required', Rule::in(array_keys(Pagamento::METODOS))],
        ], ['metodo.required' => 'Escolha a forma de pagamento.', 'metodo.in' => 'Forma de pagamento inválida.']);

        try {
            $pagamentos->pagarMulta($agendamento, $request->user()->id, $data['metodo']);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->collapse()->first());
        }

        return redirect()->route('cliente.historico')
            ->with('success', 'Multa paga com sucesso! Você já pode fazer novos agendamentos.');
    }
}
