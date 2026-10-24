<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuditLogService;
use App\Services\CadastroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'current_password' => ['required_with:password', 'current_password:sanctum'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required_with' => 'Informe a senha atual para definir uma nova.',
            'current_password.current_password' => 'A senha atual está incorreta.',
        ]);

        // O cast 'hashed' do model User gera o hash da senha.
        unset($data['current_password']);
        $user->update($data);

        $extra = $request->validate([
            'data_nascimento' => ['sometimes', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            // Endereço: se vier algum campo, o endereço precisa vir completo.
            'logradouro' => ['sometimes', 'required', 'string', 'max:75'],
            'numero' => ['required_with:logradouro', 'string', 'max:10'],
            'complemento' => ['nullable', 'string', 'max:35'],
            'bairro' => ['required_with:logradouro', 'string', 'max:45'],
            'cidade' => ['required_with:logradouro', 'string', 'max:45'],
            'uf' => ['required_with:logradouro', 'string', 'size:2'],
            'cep' => ['nullable', 'regex:/^\d{8}$/'],
        ]);

        if (isset($extra['data_nascimento'])) {
            $user->update(['data_nascimento' => $extra['data_nascimento']]);
        }

        // Um endereço novo vira o principal; o antigo fica guardado porque agendamentos
        // passados apontam para ele (o histórico não pode mudar de endereço).
        if (isset($extra['logradouro'])) {
            DB::transaction(function () use ($user, $extra) {
                $user->enderecos()->update(['principal' => false]);
                $user->enderecos()->create([
                    ...CadastroService::camposEndereco([...$extra, 'cep' => $extra['cep'] ?? null, 'uf' => strtoupper($extra['uf'])]),
                    'principal' => true,
                ]);
            });
        }

        if ($user->isPrestador() && $user->prestadorProfile) {
            $perfil = $user->prestadorProfile;
            $dadosPrestador = $request->validate([
                'area_atuacao' => ['sometimes', 'nullable', 'string', 'max:100'],
                'disponibilidades' => ['sometimes', 'array'],
                'disponibilidades.*.dia_semana' => ['required', 'integer', 'between:0,6'],
                'disponibilidades.*.hora_inicio' => ['required', 'date_format:H:i'],
                'disponibilidades.*.hora_fim' => ['required', 'date_format:H:i', 'after:disponibilidades.*.hora_inicio'],
            ]);

            if (array_key_exists('area_atuacao', $dadosPrestador)) {
                $perfil->update(['area_atuacao' => $dadosPrestador['area_atuacao']]);
            }

            // A lista enviada substitui a disponibilidade anterior.
            if (isset($dadosPrestador['disponibilidades'])) {
                DB::transaction(function () use ($perfil, $dadosPrestador) {
                    $perfil->disponibilidades()->delete();
                    $perfil->disponibilidades()->createMany($dadosPrestador['disponibilidades']);
                });
            }
        }

        AuditLogService::log('perfil_atualizado', 'users', $user->id);

        $relations = match ($user->role) {
            'prestador' => ['enderecoPrincipal', 'prestadorProfile.disponibilidades'],
            'cliente' => ['enderecoPrincipal'],
            default => [],
        };

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->fresh()->load($relations)),
            'message' => 'Perfil atualizado com sucesso.',
        ]);
    }
}
