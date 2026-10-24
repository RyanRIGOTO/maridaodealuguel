<?php

namespace App\Services;

use App\Models\Disponibilidade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Criação de contas de cliente e de prestador, usada pela Web e pela API.
 */
class CadastroService
{
    // O cadastro só pergunta os dias; o horário começa neste padrão.
    public const HORARIO_PADRAO = ['08:00', '18:00'];

    /**
     * @param  array  $dados  dados já validados pelo Register*Request
     */
    public static function cadastrar(array $dados, string $role): User
    {
        // Usuário, endereço e perfil são gravados juntos para não sobrar conta pela metade.
        return DB::transaction(function () use ($dados, $role) {
            $user = User::create([
                'name' => $dados['name'],
                'email' => $dados['email'],
                'phone' => $dados['phone'] ?? null,
                'password' => $dados['password'], // o cast 'hashed' do model gera o hash
                'role' => $role,
                'cpf_cnpj' => $dados['cpf_cnpj'],
                'data_nascimento' => $dados['data_nascimento'],
                'termos_aceitos_em' => now(), // RN11: a validação 'accepted' garantiu o aceite
            ]);

            if (!empty($dados['logradouro'])) {
                $user->enderecos()->create([
                    ...static::camposEndereco($dados),
                    'principal' => true,
                ]);
            }

            if ($role === 'prestador') {
                $perfil = $user->prestadorProfile()->create(['area_atuacao' => $dados['area_atuacao'] ?? null]);

                foreach ($dados['dias_disponiveis'] ?? [] as $dia) {
                    $perfil->disponibilidades()->create([
                        'dia_semana' => array_search($dia, Disponibilidade::DIAS, true),
                        'hora_inicio' => self::HORARIO_PADRAO[0],
                        'hora_fim' => self::HORARIO_PADRAO[1],
                    ]);
                }
            }

            AuditLogService::log('usuario_criado', 'users', $user->id, ['role' => $role]);

            return $user;
        });
    }

    /** Campos de endereço de um array validado (cadastro ou "outro endereço" do agendamento). */
    public static function camposEndereco(array $dados): array
    {
        return [
            'cep' => $dados['cep'] ?: null,
            'logradouro' => $dados['logradouro'],
            'numero' => $dados['numero'],
            'complemento' => $dados['complemento'] ?? null,
            'bairro' => $dados['bairro'],
            'cidade' => $dados['cidade'],
            'uf' => $dados['uf'],
        ];
    }
}
