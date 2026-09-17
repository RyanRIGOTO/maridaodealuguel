<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\AuditLog;
use App\Models\Recebimento;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * AgendamentoService
 *
 * Centraliza as regras de negócio de agendamento compartilhadas entre a Web e a API REST:
 * - RN3: Validação de conflito de agenda (janela de 2 horas).
 * - RN4: Cancelamento de agendamentos com cálculo de antecedência/multa (< 24h).
 * - RN5: Ciclo de vida do agendamento (pendente -> confirmado -> concluido/cancelado).
 * - RN6 & RN7: Geração de recebimento e divisão financeira (10% taxa / 90% prestador).
 * - RN8: Bloqueio de novos agendamentos para clientes com avaliações pendentes.
 * - RN14: Registro de trilha de auditoria para operações críticas.
 */
class AgendamentoService
{
    /**
     * RN3: Valida se o prestador ou cliente já possuem agendamento no intervalo de 2 horas.
     *
     * @param int $prestadorId
     * @param int $clienteId
     * @param Carbon $dataHora
     * @throws ValidationException
     */
    public static function verificarConflito(int $prestadorId, int $clienteId, Carbon $dataHora): void
    {
        // Define a janela de 2h antes e 2h depois
        $inicio = $dataHora->copy()->subHours(2);
        $fim = $dataHora->copy()->addHours(2);

        // Verifica existência de agendamento ativo (pendente ou confirmado)
        $conflito = Agendamento::where(function ($q) use ($prestadorId, $inicio, $fim) {
            $q->where('prestador_id', $prestadorId)
              ->whereBetween('data_hora', [$inicio, $fim]);
        })->orWhere(function ($q) use ($clienteId, $inicio, $fim) {
            $q->where('cliente_id', $clienteId)
              ->whereBetween('data_hora', [$inicio, $fim]);
        })->whereIn('status', ['pendente', 'confirmado'])->exists();

        if ($conflito) {
            throw ValidationException::withMessages([
                'data_hora' => ['Horário indisponível para este profissional ou cliente (janela de 2h ocupada).'],
            ]);
        }
    }

    /**
     * Cria um novo agendamento com validação de status do serviço, avaliação pendente (RN8),
     * conflito de horário (RN3) e status inicial 'pendente' (RN5).
     *
     * @param int $clienteId
     * @param int $servicoId
     * @param Carbon $dataHora
     * @param string $enderecoServico
     * @return Agendamento
     * @throws ValidationException
     */
    public static function criar(int $clienteId, int $servicoId, Carbon $dataHora, string $enderecoServico): Agendamento
    {
        $servico = Servico::with('prestador')->findOrFail($servicoId);

        // Verifica se o serviço e o prestador estão ativos no sistema
        if ($servico->status !== 'ativo' || !$servico->prestador->isActive()) {
            throw ValidationException::withMessages([
                'servico_id' => ['Este serviço não está disponível no momento.'],
            ]);
        }

        // RN8: Bloqueia cliente se houver serviço concluído pendente de avaliação
        static::verificarAvaliacaoPendente($clienteId);

        // RN3: Valida conflito de horários (janela de 2h)
        static::verificarConflito($servico->prestador_id, $clienteId, $dataHora);

        // Persiste o agendamento no banco
        $agendamento = Agendamento::create([
            'cliente_id' => $clienteId,
            'prestador_id' => $servico->prestador_id,
            'servico_id' => $servico->id,
            'data_hora' => $dataHora,
            'endereco_servico' => $enderecoServico,
            'status' => 'pendente', // RN5: Status inicial sempre pendente
            'preco_acordado' => $servico->preco_sugerido,
        ]);

        // RN14: Registro na trilha de auditoria
        AuditLogService::log('agendamento_criado', 'agendamentos', $agendamento->id, [
            'cliente_id' => $clienteId,
            'prestador_id' => $servico->prestador_id,
            'servico_id' => $servico->id,
            'data_hora' => $dataHora->toISOString(),
        ]);

        return $agendamento;
    }

    /**
     * RN8: Verifica se o cliente tem serviços concluídos sem avaliação e bloqueia novos pedidos.
     *
     * @param int $clienteId
     * @throws ValidationException
     */
    public static function verificarAvaliacaoPendente(int $clienteId): void
    {
        $pendente = Agendamento::with('avaliacao')
            ->where('cliente_id', $clienteId)
            ->where('status', 'concluido')
            ->whereDoesntHave('avaliacao')
            ->exists();

        if ($pendente) {
            throw ValidationException::withMessages([
                'agendamento' => ['Você possui serviços concluídos sem avaliação. Por favor, avalie antes de realizar um novo agendamento.'],
            ]);
        }
    }

    /**
     * RN5: Prestador confirma o agendamento (pendente -> confirmado).
     *
     * @param Agendamento $agendamento
     * @param int $prestadorId
     * @return Agendamento
     * @throws ValidationException
     */
    public static function confirmar(Agendamento $agendamento, int $prestadorId): Agendamento
    {
        if ($agendamento->prestador_id !== $prestadorId) {
            throw ValidationException::withMessages(['agendamento' => ['Apenas o prestador responsável pode confirmar este agendamento.']]);
        }
        if ($agendamento->status !== 'pendente') {
            throw ValidationException::withMessages(['agendamento' => ['Apenas agendamentos pendentes podem ser confirmados.']]);
        }

        $agendamento->update(['status' => 'confirmado']);
        AuditLogService::log('agendamento_confirmado', 'agendamentos', $agendamento->id, ['prestador_id' => $prestadorId]);

        return $agendamento;
    }

    /**
     * RN5, RN6, RN7: Marca agendamento como concluído e aciona o cálculo financeiro do recebimento.
     *
     * @param Agendamento $agendamento
     * @param int $prestadorId
     * @return Agendamento
     * @throws ValidationException
     */
    public static function concluir(Agendamento $agendamento, int $prestadorId): Agendamento
    {
        if ($agendamento->prestador_id !== $prestadorId) {
            throw ValidationException::withMessages(['agendamento' => ['Apenas o prestador responsável pode concluir este agendamento.']]);
        }
        if ($agendamento->status !== 'confirmado') {
            throw ValidationException::withMessages(['agendamento' => ['Apenas agendamentos confirmados podem ser concluídos.']]);
        }

        $agendamento->update(['status' => 'concluido']);

        // RN6 & RN7: Gera registro financeiro com taxa de 10% e repasse líquido de 90%
        RecebimentoService::criar($agendamento);

        AuditLogService::log('agendamento_concluido', 'agendamentos', $agendamento->id, ['prestador_id' => $prestadorId]);

        return $agendamento;
    }

    /**
     * RN4: Cancela agendamento e calcula multa se o cancelamento ocorrer com menos de 24h de antecedência.
     *
     * @param Agendamento $agendamento
     * @param int $userId
     * @return Agendamento
     * @throws ValidationException
     */
    public static function cancelar(Agendamento $agendamento, int $userId): Agendamento
    {
        if (!in_array($agendamento->status, ['pendente', 'confirmado'])) {
            throw ValidationException::withMessages(['agendamento' => ['Este agendamento não pode ser cancelado no status atual.']]);
        }

        $agora = now();
        $dataHora = Carbon::parse($agendamento->data_hora);
        // Calcula horas restantes até o início do serviço
        $horasAntecedencia = $agora->diffInHours($dataHora, false);
        $multa = null;

        // Se o cancelamento for feito com menos de 24h de antecedência, aplica multa de 20%
        if ($horasAntecedencia >= 0 && $horasAntecedencia < 24) {
            $multa = round($agendamento->preco_acordado * 0.20, 2);
        }

        $agendamento->update(['status' => 'cancelado']);

        // RN14: Auditoria de cancelamento
        AuditLogService::log('agendamento_cancelado', 'agendamentos', $agendamento->id, [
            'cancelado_por' => $userId,
            'horas_antecedencia' => $horasAntecedencia,
            'multa' => $multa,
        ]);

        return $agendamento;
    }
}