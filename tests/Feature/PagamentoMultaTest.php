<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Servico;
use App\Models\User;
use App\Pagamentos\GatewayPagamento;
use App\Pagamentos\ResultadoPagamento;
use Tests\TestCase;

class PagamentoMultaTest extends TestCase
{
    private User $cliente;
    private Agendamento $cancelado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cliente = User::factory()->create(['role' => 'cliente']);
        $this->cancelado = Agendamento::factory()->create([
            'cliente_id' => $this->cliente->id,
            'status' => 'cancelado',
        ]);
        $this->cancelado->cancelamento()->create(['cancelado_por' => $this->cliente->id, 'multa' => 20.00]);
    }

    private function novoAgendamento()
    {
        $servico = Servico::factory()->create();

        return $this->actingAs($this->cliente)->from('/cliente/agendar')->post('/cliente/agendar', [
            'servico_id' => $servico->id,
            'data' => now()->addDays(3)->toDateString(),
            'hora' => '10:00',
            'endereco_opcao' => 'cadastro',
            'endereco_id' => $this->cancelado->endereco_id,
        ]);
    }

    public function test_cliente_com_multa_pendente_nao_agenda(): void
    {
        $this->novoAgendamento()->assertSessionHasErrors('agendamento');
        $this->assertSame(1, Agendamento::count());
    }

    public function test_cliente_ve_aviso_e_paga_a_multa_e_volta_a_agendar(): void
    {
        $this->actingAs($this->cliente)->get('/cliente/dashboard')
            ->assertSee('Pagar multa')->assertSee('R$ 20,00')
            ->assertSee($this->cancelado->servico->nome)
            ->assertSee($this->cancelado->data_hora->format('d/m/Y \à\s H:i'));

        $this->get("/cliente/multas/{$this->cancelado->id}/pagar")->assertOk()->assertSee('Pagamento simulado');

        $this->post("/cliente/multas/{$this->cancelado->id}/pagar", ['metodo' => 'pix'])
            ->assertRedirect('/cliente/historico')->assertSessionHas('success');

        $this->assertDatabaseHas('pagamentos', [
            'agendamento_id' => $this->cancelado->id, 'tipo' => 'multa', 'status' => 'aprovado', 'gateway' => 'simulado', 'valor' => 20.00,
        ]);
        // RN4: só depois do pagamento a multa vira recebimento do prestador.
        $this->assertDatabaseHas('recebimentos', ['agendamento_id' => $this->cancelado->id, 'tipo' => 'multa', 'valor_total' => 20.00]);

        $this->get('/cliente/dashboard')->assertDontSee('Pagar multa');
        $this->novoAgendamento()->assertSessionHasNoErrors()->assertRedirect(route('cliente.historico'));
    }

    public function test_multa_nao_e_paga_duas_vezes(): void
    {
        $this->actingAs($this->cliente)->post("/cliente/multas/{$this->cancelado->id}/pagar", ['metodo' => 'pix']);
        $this->post("/cliente/multas/{$this->cancelado->id}/pagar", ['metodo' => 'pix'])->assertSessionHas('error');

        $this->assertDatabaseCount('pagamentos', 1);
        $this->assertDatabaseCount('recebimentos', 1);
    }

    public function test_outro_cliente_nao_paga_nem_ve_a_multa_alheia(): void
    {
        $outro = User::factory()->create(['role' => 'cliente']);

        $this->actingAs($outro)->get("/cliente/multas/{$this->cancelado->id}/pagar")->assertRedirect('/cliente/historico');
        $this->post("/cliente/multas/{$this->cancelado->id}/pagar", ['metodo' => 'pix'])->assertSessionHas('error');
        $this->assertDatabaseCount('pagamentos', 0);
    }

    public function test_metodo_invalido_e_rejeitado(): void
    {
        $this->actingAs($this->cliente)->from("/cliente/multas/{$this->cancelado->id}/pagar")
            ->post("/cliente/multas/{$this->cancelado->id}/pagar", ['metodo' => 'bitcoin'])
            ->assertSessionHasErrors('metodo');
    }

    public function test_pagamento_recusado_fica_registrado_e_mantem_o_bloqueio(): void
    {
        // Gateway falso que sempre recusa: é assim que um provedor real seria plugado.
        $this->app->bind(GatewayPagamento::class, fn () => new class implements GatewayPagamento {
            public function nome(): string { return 'teste'; }
            public function cobrar(float $valor, string $metodo, string $descricao): ResultadoPagamento
            {
                return new ResultadoPagamento(false);
            }
        });

        $this->actingAs($this->cliente)->post("/cliente/multas/{$this->cancelado->id}/pagar", ['metodo' => 'cartao'])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('pagamentos', ['agendamento_id' => $this->cancelado->id, 'status' => 'recusado', 'gateway' => 'teste']);
        $this->assertDatabaseCount('recebimentos', 0);
        $this->novoAgendamento()->assertSessionHasErrors('agendamento');
    }
}
