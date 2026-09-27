<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RecebimentoService;
use App\Models\Agendamento;
use App\Models\Recebimento;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Servico;
use Carbon\Carbon;

class RecebimentoServiceTest extends TestCase
{
    protected User $prestador;
    protected User $cliente;
    protected Servico $servico;
    protected Agendamento $agendamentoConcluido;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cliente = User::factory()->create(['role' => 'cliente', 'status' => 'ativo']);
        $this->prestador = User::factory()->create(['role' => 'prestador', 'status' => 'ativo']);
        $this->prestador->prestadorProfile()->create([
            'endereco_completo' => 'Rua Teste, 123',
            'data_nascimento' => '1990-01-01',
            'area_atuacao' => 'São Paulo',
            'disponibilidade_horarios' => [],
            'reputacao_media' => 5.00,
            'em_revisao' => false,
        ]);

        $categoria = \App\Models\Categoria::factory()->create(['nome_categoria' => 'Teste']);

        $this->servico = Servico::factory()->create([
            'prestador_id' => $this->prestador->id,
            'categoria_id' => $categoria->id,
            'status' => 'ativo',
            'preco_sugerido' => 100.00,
        ]);

        $this->agendamentoConcluido = Agendamento::factory()->create([
            'cliente_id' => $this->cliente->id,
            'prestador_id' => $this->prestador->id,
            'servico_id' => $this->servico->id,
            'status' => 'concluido',
            'data_hora' => Carbon::now()->subDays(1),
            'preco_acordado' => 200.00,
        ]);
    }

    public function test_criar_recebimento_rn6_rn7_calculo_correto(): void
    {
        $recebimento = RecebimentoService::criar($this->agendamentoConcluido);

        $this->assertInstanceOf(Recebimento::class, $recebimento);
        $this->assertEquals(200.00, $recebimento->valor_total);
        $this->assertEquals(20.00, $recebimento->taxa_admin); // RN7: 10%
        $this->assertEquals(180.00, $recebimento->valor_liquido_prestador); // RN6: 90%
        $this->assertEquals('pendente', $recebimento->status_recebimento);
        
        // RN6: Liberação em 48h
        $esperadoLiberacao = now()->addHours(48);
        $this->assertTrue($recebimento->data_liberacao->diffInMinutes($esperadoLiberacao, false) < 2);

        // RN14: Auditoria
        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'recebimento_criado',
            'entidade' => 'recebimentos',
            'entidade_id' => $recebimento->id,
        ]);
    }

    public function test_criar_recebimento_valores_diferentes(): void
    {
        $agendamento = Agendamento::factory()->create([
            'prestador_id' => $this->prestador->id,
            'status' => 'concluido',
            'preco_acordado' => 350.50,
        ]);

        $recebimento = RecebimentoService::criar($agendamento);

        $this->assertEquals(350.50, $recebimento->valor_total);
        $this->assertEquals(35.05, $recebimento->taxa_admin); // 10% arredondado
        $this->assertEquals(315.45, $recebimento->valor_liquido_prestador);
    }

    public function test_liberar_recebimento_muda_status_para_pago(): void
    {
        $recebimento = Recebimento::factory()->create([
            'agendamento_id' => $this->agendamentoConcluido->id,
            'status_recebimento' => 'pendente',
        ]);

        $recebimentoLiberado = app(RecebimentoService::class)->liberar($recebimento);

        $this->assertEquals('pago', $recebimentoLiberado->status_recebimento);
        $this->assertTrue($recebimentoLiberado->data_liberacao->isPast());

        // RN14: Auditoria
        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'recebimento_liberado',
            'entidade' => 'recebimentos',
            'entidade_id' => $recebimento->id,
        ]);
    }

    public function test_liberar_recebimento_ja_pago_mantem_pago(): void
    {
        $recebimento = Recebimento::factory()->create([
            'agendamento_id' => $this->agendamentoConcluido->id,
            'status_recebimento' => 'pago',
        ]);

        $recebimentoLiberado = app(RecebimentoService::class)->liberar($recebimento);

        $this->assertEquals('pago', $recebimentoLiberado->status_recebimento);
    }
}