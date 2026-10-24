<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AgendamentoService;
use App\Models\User;
use App\Models\Servico;
use App\Models\Agendamento;
use App\Models\Configuracao;
use App\Models\Endereco;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AgendamentoServiceTest extends TestCase
{
    protected User $cliente;
    protected User $prestador;
    protected Servico $servico;
    protected Endereco $endereco;

    protected function setUp(): void
    {
        parent::setUp();

        // Os valores esperados destes testes usam taxa de 10% e multa de 20%, definidas pelo administrador.
        Configuracao::definir(Configuracao::TAXA_PLATAFORMA, '10.00');
        Configuracao::definir(Configuracao::MULTA_CANCELAMENTO, '20.00');

        $this->cliente = User::factory()->create([
            'role' => 'cliente',
            'status' => 'ativo',
        ]);

        $this->prestador = User::factory()->create([
            'role' => 'prestador',
            'status' => 'ativo',
        ]);

        $this->prestador->prestadorProfile()->create(['area_atuacao' => 'São Paulo']);

        $categoria = \App\Models\Categoria::factory()->create(['nome_categoria' => 'Teste']);

        $this->endereco = Endereco::factory()->create(['user_id' => $this->cliente->id, 'principal' => true]);

        $this->servico = Servico::factory()->create([
            'prestador_id' => $this->prestador->id,
            'categoria_id' => $categoria->id,
            'status' => 'ativo',
            'preco_sugerido' => 100.00,
        ]);
    }

    public function test_criar_agendamento_sucesso_rn5(): void
    {
        $dataHora = Carbon::now()->addDays(2)->setTime(10, 0);
        $endereco = $this->endereco->id;

        $agendamento = AgendamentoService::criar(
            $this->cliente->id,
            $this->servico->id,
            $dataHora,
            $endereco
        );

        $this->assertInstanceOf(Agendamento::class, $agendamento);
        $this->assertEquals('pendente', $agendamento->status); // RN5: Status inicial pendente
        $this->assertEquals($this->cliente->id, $agendamento->cliente_id);
        $this->assertEquals($this->prestador->id, $agendamento->prestador_id);
        $this->assertEquals($this->servico->id, $agendamento->servico_id);
        $this->assertEquals(100.00, $agendamento->preco_acordado);

        // RN14: Auditoria
        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'agendamento_criado',
            'entidade' => 'agendamentos',
            'entidade_id' => $agendamento->id,
        ]);
    }

    public function test_criar_agendamento_falha_servico_inativo(): void
    {
        $this->servico->update(['status' => 'inativo']);
        $dataHora = Carbon::now()->addDays(2)->setTime(10, 0);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Este serviço não está disponível no momento.');

        AgendamentoService::criar($this->cliente->id, $this->servico->id, $dataHora, $this->endereco->id);
    }

    public function test_criar_agendamento_falha_prestador_inativo(): void
    {
        $this->prestador->update(['status' => 'inativo']);
        $dataHora = Carbon::now()->addDays(2)->setTime(10, 0);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Este serviço não está disponível no momento.');

        AgendamentoService::criar($this->cliente->id, $this->servico->id, $dataHora, $this->endereco->id);
    }

    public function test_verificar_conflito_rn3_prestador_mesmo_horario(): void
    {
        // Cria agendamento existente
        $dataHora = Carbon::now()->addDays(2)->setTime(10, 0);
        Agendamento::factory()->create([
            'prestador_id' => $this->prestador->id,
            'data_hora' => $dataHora,
            'status' => 'confirmado',
        ]);

        // Tenta criar outro no mesmo horário (dentro da janela de 2h)
        $novaDataHora = $dataHora->copy()->addHour();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Horário indisponível');

        AgendamentoService::verificarConflito($this->prestador->id, $this->cliente->id, $novaDataHora);
    }

    public function test_verificar_conflito_rn3_cliente_mesmo_horario(): void
    {
        $dataHora = Carbon::now()->addDays(2)->setTime(10, 0);
        Agendamento::factory()->create([
            'cliente_id' => $this->cliente->id,
            'data_hora' => $dataHora,
            'status' => 'confirmado',
        ]);

        $novaDataHora = $dataHora->copy()->addHour();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Horário indisponível');

        AgendamentoService::verificarConflito($this->prestador->id, $this->cliente->id, $novaDataHora);
    }

    public function test_verificar_conflito_rn3_permite_fora_janela_2h(): void
    {
        $dataHora = Carbon::now()->addDays(2)->setTime(10, 0);
        Agendamento::factory()->create([
            'prestador_id' => $this->prestador->id,
            'data_hora' => $dataHora,
            'status' => 'confirmado',
        ]);

        // Fora da janela de 2h (3 horas depois)
        $novaDataHora = $dataHora->copy()->addHours(3);

        // Não deve lançar exceção
        AgendamentoService::verificarConflito($this->prestador->id, $this->cliente->id, $novaDataHora);
        $this->assertTrue(true);
    }

    public function test_criar_agendamento_bloqueia_avaliacao_pendente_rn8(): void
    {
        // Cria agendamento concluído sem avaliação
        $agendamentoAntigo = Agendamento::factory()->create([
            'cliente_id' => $this->cliente->id,
            'prestador_id' => $this->prestador->id,
            'servico_id' => $this->servico->id,
            'status' => 'concluido',
            'data_hora' => Carbon::now()->subDays(5),
        ]);

        $dataHora = Carbon::now()->addDays(2)->setTime(10, 0);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Você possui serviços concluídos sem avaliação');

        AgendamentoService::criar($this->cliente->id, $this->servico->id, $dataHora, $this->endereco->id);
    }

    public function test_confirmar_agendamento_rn5_sucesso(): void
    {
        $agendamento = Agendamento::factory()->create([
            'prestador_id' => $this->prestador->id,
            'status' => 'pendente',
        ]);

        $resultado = AgendamentoService::confirmar($agendamento, $this->prestador->id);

        $this->assertEquals('confirmado', $resultado->status);
        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'agendamento_confirmado',
            'entidade_id' => $agendamento->id,
        ]);
    }

    public function test_confirmar_agendamento_falha_nao_e_prestador(): void
    {
        $outroPrestador = User::factory()->create(['role' => 'prestador']);
        $agendamento = Agendamento::factory()->create([
            'prestador_id' => $this->prestador->id,
            'status' => 'pendente',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Apenas o prestador responsável pode confirmar');

        AgendamentoService::confirmar($agendamento, $outroPrestador->id);
    }

    public function test_confirmar_agendamento_falha_nao_pendente(): void
    {
        $agendamento = Agendamento::factory()->create([
            'prestador_id' => $this->prestador->id,
            'status' => 'confirmado', // já confirmado
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Apenas agendamentos pendentes podem ser confirmados');

        AgendamentoService::confirmar($agendamento, $this->prestador->id);
    }

    public function test_concluir_agendamento_rn5_rn6_rn7_sucesso(): void
    {
        $agendamento = Agendamento::factory()->create([
            'prestador_id' => $this->prestador->id,
            'status' => 'confirmado',
            'preco_acordado' => 200.00,
        ]);

        $resultado = AgendamentoService::concluir($agendamento, $this->prestador->id);

        $this->assertEquals('concluido', $resultado->status);

        // RN6 & RN7: Recebimento criado com 10% taxa e 90% prestador
        $this->assertDatabaseHas('recebimentos', [
            'agendamento_id' => $agendamento->id,
            'valor_total' => 200.00,
            'percentual_taxa' => 10.00, // taxa (R$ 20) e líquido (R$ 180) são calculados
            'status_recebimento' => 'pendente',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'agendamento_concluido',
            'entidade_id' => $agendamento->id,
        ]);
    }

    public function test_concluir_agendamento_falha_nao_confirmado(): void
    {
        $agendamento = Agendamento::factory()->create([
            'prestador_id' => $this->prestador->id,
            'status' => 'pendente',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Apenas agendamentos confirmados podem ser concluídos');

        AgendamentoService::concluir($agendamento, $this->prestador->id);
    }

    public function test_cancelar_agendamento_rn4_sem_multa_mais_24h(): void
    {
        $agendamento = Agendamento::factory()->create([
            'cliente_id' => $this->cliente->id,
            'prestador_id' => $this->prestador->id,
            'servico_id' => $this->servico->id,
            'status' => 'confirmado',
            'data_hora' => Carbon::now()->addDays(2),
            'preco_acordado' => 100.00,
        ]);

        $resultado = AgendamentoService::cancelar($agendamento, $this->cliente->id);

        $this->assertEquals('cancelado', $resultado->status);
        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'agendamento_cancelado',
            'entidade_id' => $agendamento->id,
            'detalhes->multa' => null, // Sem multa
        ]);
    }

    public function test_cancelar_agendamento_rn4_com_multa_menos_24h(): void
    {
        $agendamento = Agendamento::factory()->create([
            'cliente_id' => $this->cliente->id,
            'prestador_id' => $this->prestador->id,
            'servico_id' => $this->servico->id,
            'status' => 'confirmado',
            'data_hora' => Carbon::now()->addHours(12), // 12h de antecedência
            'preco_acordado' => 100.00,
        ]);

        $resultado = AgendamentoService::cancelar($agendamento, $this->cliente->id);

        $this->assertEquals('cancelado', $resultado->status);
        $auditoria = AuditLog::where('acao', 'agendamento_cancelado')
            ->where('entidade_id', $agendamento->id)->sole();
        $this->assertEquals(20.00, $auditoria->detalhes['multa']); // 20% de multa

        // RN4: a multa fica no agendamento; o recebimento do prestador só nasce quando o cliente paga.
        $this->assertEquals(20.00, $resultado->cancelamento->multa);
        $this->assertSame($this->cliente->id, $resultado->cancelamento->cancelado_por);
        $this->assertDatabaseMissing('recebimentos', ['agendamento_id' => $agendamento->id]);
    }

    public function test_cancelar_pelo_prestador_com_menos_de_24h_nao_gera_multa(): void
    {
        $agendamento = Agendamento::factory()->create([
            'cliente_id' => $this->cliente->id,
            'prestador_id' => $this->prestador->id,
            'servico_id' => $this->servico->id,
            'status' => 'confirmado',
            'data_hora' => Carbon::now()->addHours(12),
            'preco_acordado' => 100.00,
        ]);

        $resultado = AgendamentoService::cancelar($agendamento, $this->prestador->id);

        $this->assertNull($resultado->cancelamento->multa);
        $this->assertDatabaseMissing('recebimentos', ['agendamento_id' => $agendamento->id]);
    }

    public function test_multa_usa_o_percentual_definido_pelo_admin(): void
    {
        Configuracao::definir(Configuracao::MULTA_CANCELAMENTO, '50.00');
        $agendamento = Agendamento::factory()->create([
            'cliente_id' => $this->cliente->id,
            'prestador_id' => $this->prestador->id,
            'servico_id' => $this->servico->id,
            'status' => 'pendente',
            'data_hora' => Carbon::now()->addHours(5),
            'preco_acordado' => 100.00,
        ]);

        $this->assertEquals(50.00, AgendamentoService::multaPrevista($agendamento, $this->cliente->id));
        $this->assertNull(AgendamentoService::multaPrevista($agendamento, $this->prestador->id));
    }

    public function test_criar_agendamento_bloqueado_para_prestador_em_revisao_rn10(): void
    {
        $this->prestador->prestadorProfile->update(['em_revisao' => true]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Este prestador está temporariamente indisponível para novos agendamentos.');

        AgendamentoService::criar($this->cliente->id, $this->servico->id, Carbon::now()->addDays(2), $this->endereco->id);
    }

    public function test_criar_agendamento_bloqueado_quando_prazo_de_30_dias_venceu_sem_rotina(): void
    {
        // A rotina diária ainda não rodou, mas o prazo já passou: o próprio agendamento aplica a RN10.
        $this->prestador->prestadorProfile->update(['abaixo_media_desde' => now()->subDays(31)]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Este prestador está temporariamente indisponível para novos agendamentos.');

        AgendamentoService::criar($this->cliente->id, $this->servico->id, Carbon::now()->addDays(2), $this->endereco->id);
    }

    public function test_cancelar_agendamento_falha_status_invalido(): void
    {
        $agendamento = Agendamento::factory()->create([
            'cliente_id' => $this->cliente->id,
            'prestador_id' => $this->prestador->id,
            'servico_id' => $this->servico->id,
            'status' => 'concluido',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Este agendamento não pode ser cancelado no status atual');

        AgendamentoService::cancelar($agendamento, $this->cliente->id);
    }

    public function test_cancelar_agendamento_falha_quem_nao_participa(): void
    {
        $agendamento = Agendamento::factory()->create([
            'cliente_id' => $this->cliente->id,
            'prestador_id' => $this->prestador->id,
            'servico_id' => $this->servico->id,
            'status' => 'pendente',
        ]);
        $intruso = User::factory()->create(['role' => 'cliente']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Você não pode cancelar este agendamento.');

        AgendamentoService::cancelar($agendamento, $intruso->id);
    }

    public function test_verificar_conflito_ignora_agendamentos_cancelados_e_concluidos(): void
    {
        $dataHora = Carbon::now()->addDays(3)->setTime(10, 0);
        foreach (['cancelado', 'concluido'] as $status) {
            Agendamento::factory()->create([
                'prestador_id' => $this->prestador->id,
                'servico_id' => $this->servico->id,
                'data_hora' => $dataHora,
                'status' => $status,
            ]);
        }

        // Antes da correção, o OR sem parênteses fazia esses registros bloquearem a agenda do prestador.
        AgendamentoService::verificarConflito($this->prestador->id, $this->cliente->id, $dataHora);
        $this->assertTrue(true);
    }

    public function test_criar_agendamento_falha_quando_quem_pede_nao_e_cliente(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Apenas clientes podem solicitar agendamentos.');

        AgendamentoService::criar($this->prestador->id, $this->servico->id, Carbon::now()->addDays(2), $this->endereco->id);
    }

    public function test_criar_agendamento_falha_em_categoria_inativa(): void
    {
        $this->servico->categoria->update(['status' => 'inativo']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Este serviço não está disponível no momento.');

        AgendamentoService::criar($this->cliente->id, $this->servico->id, Carbon::now()->addDays(2), $this->endereco->id);
    }
}
