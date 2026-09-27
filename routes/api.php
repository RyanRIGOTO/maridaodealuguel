<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminReportController;
use App\Http\Controllers\Api\AgendamentoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvaliacaoController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\PerfilController;
use App\Http\Controllers\Api\PublicoController;
use App\Http\Controllers\Api\ServicoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas da API - Maridão de Aluguel
|--------------------------------------------------------------------------
| Prefixo: /api
*/

// Verificação de disponibilidade
Route::get('/health', fn() => response()->json(['status' => 'ok', 'app' => 'Maridão de Aluguel', 'version' => '1.0']));

// Rotas Públicas (RN1)
Route::get('/categorias', [CategoriaController::class, 'index']);
Route::get('/servicos/publicos', [ServicoController::class, 'index']);
Route::get('/servicos/{servico}', [PublicoController::class, 'servicoShow']);

// Busca de prestadores por categoria e área (Requisito TCC)
Route::get('/prestadores', [PublicoController::class, 'prestadores']);
Route::get('/prestadores/{user}', [PublicoController::class, 'prestadorShow']);
Route::get('/prestadores/{user}/servicos', [PublicoController::class, 'servicosPorPrestador']);

// Avaliações públicas
Route::get('/avaliacoes/publicas', [PublicoController::class, 'avaliacoesPublicas']);

// Autenticação (RN1 - público)
Route::post('/auth/register/cliente', [AuthController::class, 'registerCliente']);
Route::post('/auth/register/prestador', [AuthController::class, 'registerPrestador']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Rotas protegidas com Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/deactivate', [AuthController::class, 'deactivate']); // RN12

    // Perfil (atualizar dados próprios)
    Route::put('/perfil', [PerfilController::class, 'update']);

    // Agendamentos
    Route::get('/agendamentos', [AgendamentoController::class, 'index']);
    Route::post('/agendamentos', [AgendamentoController::class, 'store']);
    Route::post('/agendamentos/{agendamento}/confirmar', [AgendamentoController::class, 'confirmar']); // RN5
    Route::post('/agendamentos/{agendamento}/concluir', [AgendamentoController::class, 'concluir']);   // RN6
    Route::post('/agendamentos/{agendamento}/cancelar', [AgendamentoController::class, 'cancelar']);  // RN4
    Route::post('/agendamentos/verificar-conflito', [AgendamentoController::class, 'verificarConflito']); // RN3

    // Serviços do Prestador
    Route::get('/servicos', [ServicoController::class, 'meusServicos']);
    Route::post('/servicos', [ServicoController::class, 'store']);
    Route::put('/servicos/{servico}', [ServicoController::class, 'update']);
    Route::delete('/servicos/{servico}', [ServicoController::class, 'destroy']);

    // Avaliações
    Route::get('/avaliacoes/pendentes', [AvaliacaoController::class, 'checkPendentes']); // RN8
    Route::post('/avaliacoes', [AvaliacaoController::class, 'store']); // RN10
    Route::get('/avaliacoes', [AvaliacaoController::class, 'index']);

    // Chat
    Route::get('/chat/conversas', [ChatController::class, 'listarConversas']);
    Route::get('/chat/{agendamento}', [ChatController::class, 'index']);
    Route::post('/chat', [ChatController::class, 'store']);

    // Admin (RN9 + Dashboard)
    Route::middleware('role:admin')->group(function () {
        // Dashboard KPIs
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);

        // Gestão de Usuários
        Route::get('/admin/usuarios', [AdminController::class, 'usuarios']);
        Route::post('/admin/usuarios/{user}/toggle-status', [AdminController::class, 'toggleUserStatus']);

        // Gestão de Categorias
        Route::get('/admin/categorias', [CategoriaController::class, 'adminIndex']);
        Route::post('/admin/categorias', [CategoriaController::class, 'store']);
        Route::put('/admin/categorias/{categoria}', [CategoriaController::class, 'update']);
        Route::delete('/admin/categorias/{categoria}', [CategoriaController::class, 'destroy']);

        // Moderação de Avaliações (RN9)
        Route::get('/admin/avaliacoes', [AdminReportController::class, 'moderarAvaliacoes']);
        Route::post('/admin/avaliacoes/{avaliacao}/moderar', [AdminReportController::class, 'moderarAvaliacao']);
        Route::delete('/admin/avaliacoes/{avaliacao}', [AdminReportController::class, 'deletarAvaliacao']);

        // Relatórios Estatísticos (RN13)
        Route::get('/admin/relatorios/servicos-por-categoria', [AdminReportController::class, 'servicosPorCategoria']);
        Route::get('/admin/relatorios/atendimentos-por-prestador', [AdminReportController::class, 'atendimentosPorPrestador']);
        Route::get('/admin/relatorios/financeiro', [AdminReportController::class, 'financeiro']);
        Route::get('/admin/auditoria', [AdminReportController::class, 'auditoria']); // RN14
    });
});
