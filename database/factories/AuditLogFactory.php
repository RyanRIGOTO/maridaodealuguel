<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Gera registros de auditoria para os testes.
 *
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'acao' => 'agendamento_criado',
            'entidade' => 'agendamentos',
            'entidade_id' => null,
            'ip_address' => '127.0.0.1',
            'detalhes' => null,
            'created_at' => now(),
        ];
    }
}
