<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Agendamento;
use App\Models\Recebimento;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\AvaliacaoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $totalClientes = User::where('role', 'cliente')->where('status', 'ativo')->count();
        $totalPrestadores = User::where('role', 'prestador')->where('status', 'ativo')->count();
        $totalAgendamentos = Agendamento::count();

        $faturamentoBruto = (float) Recebimento::sum('valor_total');
        $taxaPlataforma = (float) Recebimento::sum(DB::raw(Recebimento::SQL_TAXA));
        $faturamentoLiquido = (float) Recebimento::where('status_recebimento', 'pago')->sum(DB::raw(Recebimento::SQL_LIQUIDO));
        $pendenteLiberacao = (float) Recebimento::where('status_recebimento', 'pendente')->sum('valor_total');

        // A reputação é calculada (não gravada): médias de todos numa consulta só.
        $prestadores = User::where('role', 'prestador')->get(['id', 'name']);
        $medias = AvaliacaoService::medias($prestadores->pluck('id')->all());
        $prestadoresBaixaNota = $prestadores->filter(fn ($u) => ($medias[$u->id] ?? 5) < AvaliacaoService::NOTA_MINIMA)->values();

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
                        'reputacao_media' => $medias[$u->id],
                    ]),
                ],
            ],
        ]);
    }

    public function usuarios(Request $request): JsonResponse
    {
        $query = User::with(['enderecoPrincipal', 'prestadorProfile']);

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

        $usuarios = $query->latest()->paginate($this->porPagina($request, 20));

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($usuarios)->response()->getData(true),
        ]);
    }

    public function toggleUserStatus(Request $request, User $user): JsonResponse
    {
        // Contas de administrador são protegidas, como na tela Web (evita o admin se trancar fora).
        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Contas de administrador não podem ser inativadas por aqui.'],
            ], 403);
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
            ['status' => $novoStatus, 'admin_id' => $request->user()->id]
        );

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'Usuário ' . ($novoStatus === 'ativo' ? 'ativado' : 'inativado') . ' com sucesso.',
        ]);
    }
}
