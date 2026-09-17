<?php

use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ChatController;
use App\Http\Controllers\Web\ClienteController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\PrestadorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Web - Maridão de Aluguel (Blade + Tailwind)
|--------------------------------------------------------------------------
| Front-end renderizado no servidor com Blade. As regras de negócio ficam
| nos Services (app/Services), reaproveitados aqui e pela API (routes/api.php).
*/

// ---- Público -------------------------------------------------------------
Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/cadastro/cliente', [AuthController::class, 'showRegisterCliente'])->name('register.cliente');
Route::post('/cadastro/cliente', [AuthController::class, 'registerCliente'])->name('register.cliente.store');
Route::get('/cadastro/prestador', [AuthController::class, 'showRegisterPrestador'])->name('register.prestador');
Route::post('/cadastro/prestador', [AuthController::class, 'registerPrestador'])->name('register.prestador.store');

// ---- Cliente ---------------------------------------------------------------
Route::middleware(['auth', 'role.web:cliente'])->prefix('cliente')->name('cliente.')->group(function () {
    Route::get('/dashboard', [ClienteController::class, 'dashboard'])->name('dashboard');
    Route::get('/agendar', [ClienteController::class, 'agendar'])->name('agendar');
    Route::post('/agendar', [ClienteController::class, 'agendarStore'])->name('agendar.store');
    Route::get('/historico', [ClienteController::class, 'historico'])->name('historico');
    Route::patch('/agendamentos/{agendamento}/cancelar', [ClienteController::class, 'cancelar'])->name('agendamentos.cancelar');
    Route::get('/avaliacoes', [ClienteController::class, 'avaliacoes'])->name('avaliacoes');
    Route::post('/avaliacoes/{agendamento}', [ClienteController::class, 'avaliar'])->name('avaliacoes.store');
});

// ---- Prestador ---------------------------------------------------------------
Route::middleware(['auth', 'role.web:prestador'])->prefix('prestador')->name('prestador.')->group(function () {
    Route::get('/dashboard', [PrestadorController::class, 'dashboard'])->name('dashboard');
    Route::get('/agendamentos', [PrestadorController::class, 'agendamentos'])->name('agendamentos');
    Route::patch('/agendamentos/{agendamento}/confirmar', [PrestadorController::class, 'confirmar'])->name('agendamentos.confirmar');
    Route::patch('/agendamentos/{agendamento}/concluir', [PrestadorController::class, 'concluir'])->name('agendamentos.concluir');
    Route::patch('/agendamentos/{agendamento}/cancelar', [PrestadorController::class, 'cancelar'])->name('agendamentos.cancelar');
    Route::get('/servicos', [PrestadorController::class, 'servicos'])->name('servicos');
    Route::post('/servicos', [PrestadorController::class, 'servicosStore'])->name('servicos.store');
    Route::patch('/servicos/{servico}', [PrestadorController::class, 'servicosUpdate'])->name('servicos.update');
    Route::delete('/servicos/{servico}', [PrestadorController::class, 'servicosDestroy'])->name('servicos.destroy');
    Route::get('/historico', [PrestadorController::class, 'historico'])->name('historico');
    Route::get('/relatorios/servicos', [PrestadorController::class, 'relatorioServicos'])->name('relatorios.servicos');
});

// ---- Admin ---------------------------------------------------------------
Route::middleware(['auth', 'role.web:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/usuarios', [AdminController::class, 'usuarios'])->name('usuarios');
    Route::patch('/usuarios/{user}/status', [AdminController::class, 'usuarioStatus'])->name('usuarios.status');

    Route::get('/categorias', [AdminController::class, 'categorias'])->name('categorias');
    Route::post('/categorias', [AdminController::class, 'categoriasStore'])->name('categorias.store');
    Route::patch('/categorias/{categoria}', [AdminController::class, 'categoriasUpdate'])->name('categorias.update');
    Route::delete('/categorias/{categoria}', [AdminController::class, 'categoriasDestroy'])->name('categorias.destroy');

    Route::get('/relatorios', [AdminController::class, 'relatorios'])->name('relatorios');
    Route::get('/relatorios/agendamentos', [AdminController::class, 'relatorioAgendamentos'])->name('relatorios.agendamentos');
    Route::get('/relatorios/financeiro', [AdminController::class, 'relatorioFinanceiro'])->name('relatorios.financeiro');
    Route::get('/relatorios/avaliacoes', [AdminController::class, 'relatorioAvaliacoes'])->name('relatorios.avaliacoes');
    Route::get('/relatorios/usuarios', [AdminController::class, 'relatorioUsuarios'])->name('relatorios.usuarios');
});

// ---- Chat (qualquer usuário autenticado) ----------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/{agendamento}/mensagens', [ChatController::class, 'send'])->name('chat.send');
});
