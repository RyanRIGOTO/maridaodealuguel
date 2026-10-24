<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Cancelamento;
use App\Models\Chat;
use App\Models\Configuracao;
use App\Models\Endereco;
use App\Models\PropostaHorario;
use App\Models\Servico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * AgendamentoService
 *
 * Centraliza as regras de negócio de agendamento compartilhadas entre a Web e a API REST:
 * - RN3: Validação de conflito de agenda (janela de 2 horas).
 * - RN4: Cancelamento de agendamentos com cálculo de antecedência/multa (< 24h).
 * - RN5: Ciclo de vida do agendamento (pendente -> confirmado -> concluido/cancelado).
 * - RN6 & RN7: Geração de recebimento e divisão financeira (ver RecebimentoService).
 * - RN8: Bloqueio de novos agendamentos para clientes com avaliações pendentes.
 * - RN14: Registro de trilha de auditoria para operações críticas.
 */
class AgendamentoService
{
    /**
     * RN3: Valida se o prestador ou cliente já possuem agendamento ativo no intervalo de 2 horas.
     *
     * @throws ValidationException
     */
    public static function verificarConflito(int $prestadorId, int $clienteId, Carbon $dataHora, ?int $ignorarAgendamentoId = null): void
    {
        // Define a janela de 2h antes e 2h depois
        $inicio = $dataHora->copy()->subHours(2);
        $fim = $dataHora->copy()->addHours(2);

        // O status precisa valer para os dois lados do OR; por isso as condições ficam agrupadas.
        $conflito = Agendamento::whereIn('status', ['pendente', 'confirmado'])
            ->whereBetween('data_hora', [$inicio, $fim])
            ->where(fn ($q) => $q->where('cliente_id', $clienteId)
                ->orWhereHas('servico', fn ($qq) => $qq->where('prestador_id', $prestadorId)))
            // Ao remarcar, o próprio agendamento não conta como conflito.
            ->when($ignorarAgendamentoId, fn ($q) => $q->whereKeyNot($ignorarAgendamentoId))
            ->exists();

        if ($conflito) {
            throw ValidationException::withMessages([
                'data_hora' => ['Horário indisponível para este profissional ou cliente (janela de 2h ocupada).'],
            ]);
        }
    }

    /**
     * Cria um novo agendamento com validação de status do serviço, avaliação pendente (regra de négocio 8), multa de cancelamento (RN4),
     * conflito de horário (RN3) e status inicial 'pendente' (RN5).
     *
     * @throws ValidationException
     */
    public static function criar(int $clienteId, int $servicoId, Carbon $dataHora, int $enderecoId): Agendamento
    {
        $cliente = User::findOrFail($clienteId);

        if (!$cliente->isCliente()) {
            throw ValidationException::withMessages([
                'agendamento' => ['Apenas clientes podem solicitar agendamentos.'],
            ]);
        }

        if ($dataHora->isPast()) {
            throw ValidationException::withMessages([
                'data_hora' => ['Escolha uma data e um horário futuros.'],
            ]);
        }

        // O endereço do atendimento precisa ser do próprio cliente.
        if (!Endereco::whereKey($enderecoId)->where('user_id', $clienteId)->exists()) {
            throw ValidationException::withMessages([
                'endereco_id' => ['Escolha um endereço válido para o atendimento.'],
            ]);
        }

        $servico = Servico::with(['prestador.prestadorProfile', 'categoria'])->findOrFail($servicoId);

        // Serviço, categoria e prestador precisam estar ativos no sistema
        if ($servico->status !== 'ativo' || $servico->categoria?->status !== 'ativo' || !$servico->prestador->isActive()) {
            throw ValidationException::withMessages([
                'servico_id' => ['Este serviço não está disponível no momento.'],
            ]);
        }

        // RN10: prestador em revisão por baixa reputação não recebe novos agendamentos.
        // A verificação roda aqui também para não depender só da rotina diária.
        $perfil = $servico->prestador->prestadorProfile;
        if ($perfil && AvaliacaoService::verificarRevisao($perfil)) {
            throw ValidationException::withMessages([
                'servico_id' => ['Este prestador está temporariamente indisponível para novos agendamentos.'],
            ]);
        }

        // RN8: Bloqueia cliente se houver serviço concluído pendente de avaliação
        static::verificarAvaliacaoPendente($clienteId);

        // RN4: Bloqueia cliente com multa de cancelamento não paga
        if (PagamentoService::multasPendentes($clienteId)->exists()) {
            throw ValidationException::withMessages([
                'agendamento' => ['Você tem uma multa de cancelamento pendente. Pague a multa para fazer um novo agendamento.'],
            ]);
        }

        return DB::transaction(function () use ($servico, $clienteId, $dataHora, $enderecoId) {
            // Trava o prestador para que dois pedidos simultâneos não ocupem o mesmo horário.
            User::whereKey($servico->prestador_id)->lockForUpdate()->first();

            // RN3: Valida conflito de horários (janela de 2h)
            static::verificarConflito($servico->prestador_id, $clienteId, $dataHora);

            $agendamento = Agendamento::create([
                'cliente_id' => $clienteId,
                'servico_id' => $servico->id,
                'endereco_id' => $enderecoId,
                'data_hora' => $dataHora,
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
        });
    }

    /**
     * RN8: Verifica se o cliente tem serviços concluídos sem avaliação e bloqueia novos pedidos.
     *
     * @throws ValidationException
     */
    public static function verificarAvaliacaoPendente(int $clienteId): void
    {
        $pendente = Agendamento::where('cliente_id', $clienteId)
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
     * @throws ValidationException
     */
    public static function confirmar(Agendamento $agendamento, int $prestadorId): Agendamento
    {
        return DB::transaction(function () use ($agendamento, $prestadorId) {
            $agendamento = static::travar($agendamento);

            if ($agendamento->prestador_id !== $prestadorId) {
                throw ValidationException::withMessages(['agendamento' => ['Apenas o prestador responsável pode confirmar este agendamento.']]);
            }
            if ($agendamento->status !== 'pendente') {
                throw ValidationException::withMessages(['agendamento' => ['Apenas agendamentos pendentes podem ser confirmados.']]);
            }

            $agendamento->update(['status' => 'confirmado']);
            AuditLogService::log('agendamento_confirmado', 'agendamentos', $agendamento->id, ['prestador_id' => $prestadorId]);

            return $agendamento;
        });
    }

    /**
     * RN5, RN6, RN7: Marca agendamento como concluído e aciona o cálculo financeiro do recebimento.
     * Status e recebimento são gravados juntos: ou os dois, ou nenhum.
     *
     * @throws ValidationException
     */
    public static function concluir(Agendamento $agendamento, int $prestadorId): Agendamento
    {
        return DB::transaction(function () use ($agendamento, $prestadorId) {
            $agendamento = static::travar($agendamento);

            if ($agendamento->prestador_id !== $prestadorId) {
                throw ValidationException::withMessages(['agendamento' => ['Apenas o prestador responsável pode concluir este agendamento.']]);
            }
            if ($agendamento->status !== 'confirmado') {
                throw ValidationException::withMessages(['agendamento' => ['Apenas agendamentos confirmados podem ser concluídos.']]);
            }

            $agendamento->update(['status' => 'concluido']);

            // RN6 & RN7: Gera o registro financeiro (taxa da plataforma e repasse ao prestador)
            RecebimentoService::criar($agendamento);

            AuditLogService::log('agendamento_concluido', 'agendamentos', $agendamento->id, ['prestador_id' => $prestadorId]);

            return $agendamento;
        });
    }

    /**
     * RN4: valor da multa se o cliente cancelar agora (null quando não há multa).
     * A multa compensa o prestador; por isso só vale quando quem cancela é o cliente,
     * com menos de 24h de antecedência, e o percentual vem da política definida pelo admin.
     */
    public static function multaPrevista(Agendamento $agendamento, int $userId): ?float
    {
        // Serviço grátis não tem multa.
        if ($userId !== $agendamento->cliente_id || !in_array($agendamento->status, ['pendente', 'confirmado'])
            || (float) $agendamento->preco_acordado <= 0) {
            return null;
        }

        $horasAntecedencia = now()->diffInHours($agendamento->data_hora, false);
        $percentual = Configuracao::multaCancelamento();

        if ($horasAntecedencia < 0 || $horasAntecedencia >= 24 || $percentual <= 0) {
            return null;
        }

        return round($agendamento->preco_acordado * $percentual / 100, 2);
    }

    /**
     * RN4: Cancela o agendamento. Se o cliente cancelar com menos de 24h, a multa fica
     * registrada no agendamento; o recebimento do prestador nasce quando ela é paga (PagamentoService).
     * Só o cliente ou o prestador do agendamento podem cancelá-lo.
     *
     * @throws ValidationException
     */
    public static function cancelar(Agendamento $agendamento, int $userId): Agendamento
    {
        return DB::transaction(function () use ($agendamento, $userId) {
            $agendamento = static::travar($agendamento);

            if ($agendamento->cliente_id !== $userId && $agendamento->prestador_id !== $userId) {
                throw ValidationException::withMessages(['agendamento' => ['Você não pode cancelar este agendamento.']]);
            }
            if (!in_array($agendamento->status, ['pendente', 'confirmado'])) {
                throw ValidationException::withMessages(['agendamento' => ['Este agendamento não pode ser cancelado no status atual.']]);
            }

            $horasAntecedencia = now()->diffInHours($agendamento->data_hora, false);
            $multa = static::multaPrevista($agendamento, $userId);

            $agendamento->update(['status' => 'cancelado']);
            Cancelamento::create([
                'agendamento_id' => $agendamento->id,
                'cancelado_por' => $userId,
                'multa' => $multa,
            ]);

            // RN14: Auditoria de cancelamento
            AuditLogService::log('agendamento_cancelado', 'agendamentos', $agendamento->id, [
                'cancelado_por' => $userId,
                'horas_antecedencia' => $horasAntecedencia,
                'multa' => $multa,
            ]);

            return $agendamento;
        });
    }

    /**
     * Negociação de horário: o prestador propõe uma nova data/hora pelo chat.
     * O horário só muda quando o cliente aceita (responderProposta).
     *
     * @throws ValidationException
     */
    public static function proporHorario(Agendamento $agendamento, int $prestadorId, Carbon $novaDataHora): PropostaHorario
    {
        return DB::transaction(function () use ($agendamento, $prestadorId, $novaDataHora) {
            $agendamento = static::travar($agendamento);

            if ($agendamento->prestador_id !== $prestadorId) {
                throw ValidationException::withMessages(['agendamento' => ['Apenas o prestador responsável pode propor outro horário.']]);
            }
            static::garantirRemarcavel($agendamento, $novaDataHora);

            // Só a proposta mais recente vale.
            PropostaHorario::where('agendamento_id', $agendamento->id)->where('status', 'pendente')
                ->update(['status' => 'substituida']);

            $proposta = PropostaHorario::create([
                'agendamento_id' => $agendamento->id,
                'data_hora' => $novaDataHora,
                'status' => 'pendente',
            ]);

            // A proposta chega ao cliente como uma mensagem do chat.
            Chat::create([
                'agendamento_id' => $agendamento->id,
                'remetente_id' => $prestadorId,
                'mensagem' => 'Proposta de novo horário: '.$novaDataHora->format('d/m/Y \\à\\s H:i')
                    .' (atual: '.$agendamento->data_hora->format('d/m/Y \\à\\s H:i').').',
                'proposta_horario_id' => $proposta->id,
            ]);

            AuditLogService::log('horario_proposto', 'agendamentos', $agendamento->id, [
                'de' => $agendamento->data_hora->toISOString(),
                'para' => $novaDataHora->toISOString(),
            ]);

            return $proposta;
        });
    }

    /**
     * O cliente aceita (o agendamento passa para o novo horário) ou recusa a proposta.
     * Em qualquer caso, a resposta fica registrada no chat.
     *
     * @throws ValidationException
     */
    public static function responderProposta(PropostaHorario $proposta, int $clienteId, bool $aceitar): Agendamento
    {
        return DB::transaction(function () use ($proposta, $clienteId, $aceitar) {
            $agendamento = static::travar($proposta->agendamento);
            $proposta = PropostaHorario::whereKey($proposta->id)->lockForUpdate()->firstOrFail();

            // Quem propõe é sempre o prestador; só o cliente responde.
            if ($agendamento->cliente_id !== $clienteId) {
                throw ValidationException::withMessages(['proposta' => ['Você não pode responder esta proposta.']]);
            }
            if ($proposta->status !== 'pendente') {
                throw ValidationException::withMessages(['proposta' => ['Esta proposta já foi respondida ou substituída.']]);
            }

            $novo = $proposta->data_hora;

            if ($aceitar) {
                static::garantirRemarcavel($agendamento, $novo);
                $agendamento->update(['data_hora' => $novo]);
            }

            $proposta->update(['status' => $aceitar ? 'aceita' : 'recusada']);

            Chat::create([
                'agendamento_id' => $agendamento->id,
                'remetente_id' => $clienteId,
                'mensagem' => $aceitar
                    ? 'Aceitei o novo horário: '.$novo->format('d/m/Y \\à\\s H:i').'.'
                    : 'Recusei a proposta de novo horário. O atendimento continua em '.$agendamento->data_hora->format('d/m/Y \\à\\s H:i').'.',
            ]);

            AuditLogService::log($aceitar ? 'horario_aceito' : 'horario_recusado', 'agendamentos', $agendamento->id, [
                'proposta_id' => $proposta->id,
                'data_hora' => $novo->toISOString(),
            ]);

            return $agendamento;
        });
    }

    /**
     * Regras comuns para remarcar: agendamento em aberto, horário futuro e sem conflito (RN3).
     *
     * @throws ValidationException
     */
    private static function garantirRemarcavel(Agendamento $agendamento, Carbon $novaDataHora): void
    {
        if (!in_array($agendamento->status, ['pendente', 'confirmado'])) {
            throw ValidationException::withMessages(['data_hora' => ['Só é possível remarcar agendamentos pendentes ou confirmados.']]);
        }
        if ($novaDataHora->isPast()) {
            throw ValidationException::withMessages(['data_hora' => ['Escolha uma data e um horário futuros.']]);
        }
        if ($novaDataHora->equalTo($agendamento->data_hora)) {
            throw ValidationException::withMessages(['data_hora' => ['O novo horário é igual ao atual.']]);
        }

        static::verificarConflito($agendamento->prestador_id, $agendamento->cliente_id, $novaDataHora, $agendamento->id);
    }

    /**
     * Relê o agendamento com trava de linha, para que duas ações simultâneas
     * (ex.: concluir duas vezes) não passem pela mesma verificação de status.
     */
    private static function travar(Agendamento $agendamento): Agendamento
    {
        return Agendamento::whereKey($agendamento->id)->lockForUpdate()->firstOrFail();
    }
}
