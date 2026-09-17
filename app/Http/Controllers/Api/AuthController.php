<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterClienteRequest;
use App\Http\Requests\RegisterPrestadorRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function registerCliente(RegisterClienteRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'cliente',
            'cpf_cnpj' => $request->cpf_cnpj,
            'termos_lgpd' => $request->termos_lgpd,
        ]);

        $user->clienteProfile()->create([
            'endereco_completo' => $request->endereco_completo,
            'data_nascimento' => $request->data_nascimento,
        ]);

        AuditLogService::log('usuario_criado', 'users', $user->id, ['role' => 'cliente']);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user->load('clienteProfile')),
                'token' => $token,
            ],
            'message' => 'Cadastro realizado com sucesso.',
        ], 201);
    }

    public function registerPrestador(RegisterPrestadorRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'prestador',
            'cpf_cnpj' => $request->cpf_cnpj,
            'termos_lgpd' => $request->termos_lgpd,
        ]);

        $user->prestadorProfile()->create([
            'endereco_completo' => $request->endereco_completo,
            'data_nascimento' => $request->data_nascimento,
            'area_atuacao' => $request->area_atuacao,
            'disponibilidade_horarios' => $request->disponibilidade_horarios,
            'reputacao_media' => 5.00,
            'em_revisao' => false,
        ]);

        AuditLogService::log('usuario_criado', 'users', $user->id, ['role' => 'prestador']);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => ['token' => $token, 'user' => new UserResource($user->load('prestadorProfile'))],
            'message' => 'Cadastro realizado com sucesso.',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        if ($user->status !== 'ativo') {
            throw ValidationException::withMessages([
                'email' => ['Conta inativa. Entre em contato com o suporte.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        $relations = match ($user->role) {
            'prestador' => ['prestadorProfile'],
            'cliente' => ['clienteProfile'],
            default => [],
        };

        AuditLogService::log('login', 'users', $user->id);

        return response()->json([
            'success' => true,
            'data' => ['token' => $token, 'user' => new UserResource($user->load($relations))],
            'message' => 'Login realizado com sucesso.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $relations = match($user->role) {
            'prestador' => ['prestadorProfile'],
            'cliente' => ['clienteProfile'],
            default => [],
        };

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->load($relations)),
        ]);
    }

    /**
     * RN12: Direito ao Esquecimento LGPD - usuario inativa propria conta.
     */
    public function deactivate(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'status' => 'inativo',
            'name' => 'Usuário Excluído',
            'email' => 'excluido_' . $user->id . '@anonimo.com',
            'phone' => null,
        ]);
        $user->tokens()->delete();

        AuditLogService::log('direito_esquecimento', 'users', $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Conta desativada com sucesso (LGPD - Direito ao Esquecimento).',
        ]);
    }
}
