<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    /**
     * Atualizar dados do perfil do usuário logado.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ]);

        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);

        // Atualizar profile específico por role
        if ($user->isCliente() && $user->clienteProfile) {
            $profileData = $request->validate([
                'endereco_completo' => ['sometimes', 'nullable', 'string', 'max:255'],
                'data_nascimento' => ['sometimes', 'nullable', 'date'],
            ]);
            $user->clienteProfile->update($profileData);
        } elseif ($user->isPrestador() && $user->prestadorProfile) {
            $profileData = $request->validate([
                'endereco_completo' => ['sometimes', 'nullable', 'string', 'max:255'],
                'data_nascimento' => ['sometimes', 'nullable', 'date'],
                'area_atuacao' => ['sometimes', 'nullable', 'string', 'max:100'],
                'disponibilidade_horarios' => ['sometimes', 'nullable', 'array'],
            ]);
            $user->prestadorProfile->update($profileData);
        }

        AuditLogService::log('perfil_atualizado', 'users', $user->id);

        $relations = match ($user->role) {
            'prestador' => ['prestadorProfile'],
            'cliente' => ['clienteProfile'],
            default => [],
        };

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->fresh()->load($relations)),
            'message' => 'Perfil atualizado com sucesso.',
        ]);
    }
}
