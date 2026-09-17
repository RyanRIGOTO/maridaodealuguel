<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgendamentoResource;
use App\Http\Resources\AvaliacaoResource;
use App\Http\Resources\UserResource;
use App\Models\Agendamento;
use App\Models\AuditLog;
use App\Models\Avaliacao;
use App\Models\Recebimento;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\AvaliacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $totalClientes = User::where('role', 'cliente')->where('status', 'ativo')->count();
        $totalPrestadores = User::where('role', 'prestador')->where('status', 'ativo')->count();
        $totalAgendamentos = Agendamento::count();

        $faturamentoBruto = Recebimento::sum('valor_total');
        $taxaPlataforma = Recebimento::sum('taxa_admin');
        $faturamentoLiquido = Recebimento::where('status_recebimento', 'pago')->sum('valor_liquido_prestador');
        $pendenteLiberacao = Recebimento::where('status_recebimento', 'pendente')->sum('valor_total');

        $prestadoresBaixaNota = User::where('role', 'prestador')
            ->whereHas('prestadorProfile', fn($q) => $q->where('reputacao_media', '<', 2.5))
            ->with('prestadorProfile')
            ->get();

        $emRevisao = User::where('role', 'prestador')
            ->whereHas('prestadorProfile', fn($q) => $q->where('em_revisao', true))
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'usuarios' => [
                    'total_clientes' => $totalClientes,
                    'total_prestadores' => $totalPrestadores,
                    'total_agendamentos' => $totalAgendamentos,
                ],
                'financeiro' => [
                    'faturamento_bruto' => round($faturamentoBruto, 2),
                    'taxa_plataforma' => round($taxaPlataforma, 2),
                    'faturamento_liquido_prestadores' => round($faturamentoLiquido, 2),
                    'pendente_liberacao' => round($pendenteLiberacao, 2),
                ],
                'alertas' => [
                    'prestadores_em_revisao' => $emRevisao,
                    'prestadores_baixa_nota' => $prestadoresBaixaNota->map(fn($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'reputacao_media' => $u->prestadorProfile->reputacao_media,
                    ]),
                ],
            ],
        ]);
    }

    public function usuarios(Request $request): JsonResponse
    {
        $query = User::with(['clienteProfile', 'prestadorProfile']);

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%"));
        }

        $usuarios = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($usuarios)->response()->getData(true),
        ]);
    }

    public function toggleUserStatus(User $user): JsonResponse
    {
        $novoStatus = $user->status === 'ativo' ? 'inativo' : 'ativo';
        $user->update(['status' => $novoStatus]);

        AuditLogService::log(
            $novoStatus === 'ativo' ? 'usuario_ativado' : 'usuario_inativado',
            'users',
            $user->id,
            ['status' => $novoStatus, 'admin_id' => request()->user()->id]
        );

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'Usuário ' . ($novoStatus === 'ativo' ? 'ativado' : 'inativado') . ' com sucesso.',
        ]);
    }
}
