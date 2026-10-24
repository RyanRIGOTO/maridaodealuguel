<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterClienteRequest;
use App\Http\Requests\RegisterPrestadorRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\CadastroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function registerCliente(RegisterClienteRequest $request): JsonResponse
    {
        $user = CadastroService::cadastrar($request->validated(), 'cliente');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user->load('enderecoPrincipal')),
                'token' => $token,
            ],
            'message' => 'Cadastro realizado com sucesso.',
        ], 201);
    }

    public function registerPrestador(RegisterPrestadorRequest $request): JsonResponse
    {
        $user = CadastroService::cadastrar($request->validated(), 'prestador');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user->load(['enderecoPrincipal', 'prestadorProfile.disponibilidades'])),
                'token' => $token,
            ],
            'message' => 'Cadastro realizado com sucesso.',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        // Mesma mensagem para e-mail inexistente, senha errada e conta inativa: não revela qual falhou.
        if (!$user || !Hash::check($request->password, $user->password) || !$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        $relations = match ($user->role) {
            'prestador' => ['enderecoPrincipal', 'prestadorProfile.disponibilidades'],
            'cliente' => ['enderecoPrincipal'],
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
            'prestador' => ['enderecoPrincipal', 'prestadorProfile.disponibilidades'],
            'cliente' => ['enderecoPrincipal'],
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

        // Anonimiza todos os dados pessoais; o histórico de agendamentos continua íntegro.
        DB::transaction(function () use ($user) {
            $user->update([
                'status' => 'inativo',
                'name' => 'Usuário Excluído',
                'email' => 'excluido_' . $user->id . '@anonimo.invalid',
                'phone' => null,
                'cpf_cnpj' => 'ANON' . $user->id,
                'data_nascimento' => null,
            ]);
            // Endereços ficam (agendamentos antigos apontam para eles), mas sem dados identificáveis.
            $user->enderecos()->update([
                'cep' => null, 'logradouro' => 'Endereço removido', 'numero' => 's/n',
                'complemento' => null, 'bairro' => '-', 'cidade' => '-', 'uf' => '--',
            ]);
            $user->prestadorProfile?->update(['area_atuacao' => null]);
            $user->tokens()->delete();
        });

        AuditLogService::log('direito_esquecimento', 'users', $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Conta desativada com sucesso (LGPD - Direito ao Esquecimento).',
        ]);
    }
}
