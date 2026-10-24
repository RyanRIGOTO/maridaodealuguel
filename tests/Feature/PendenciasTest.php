<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Categoria;
use App\Models\Configuracao;
use App\Models\Servico;
use App\Models\User;
use Tests\TestCase;

/**
 * Pendências resolvidas em 21/10/2026: pesquisa de categorias no agendamento,
 * buscas que estavam desativadas, multa de cancelamento (RN4) e revisão (RN10).
 */
class PendenciasTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Os valores esperados usam multa de 20% (o padrão do sistema é 50%).
        Configuracao::definir(Configuracao::MULTA_CANCELAMENTO, '20.00');
    }

    public function test_agendamento_mostra_todas_as_categorias_ativas_e_a_pesquisa(): void
    {
        $comServico = Servico::factory()->create()->categoria;
        $semServico = Categoria::factory()->create(['nome_categoria' => 'Vidraçaria']);
        $inativa = Categoria::factory()->create(['nome_categoria' => 'Categoria Desligada', 'status' => 'inativo']);

        $this->actingAs(User::factory()->create(['role' => 'cliente']))
            ->get('/cliente/agendar')->assertOk()
            ->assertSee('data-busca-categoria', false)
            ->assertSee($comServico->nome_categoria)
            ->assertSee('Vidraçaria')
            ->assertSee('Nenhum serviço disponível nesta categoria no momento.')
            ->assertDontSee('Categoria Desligada');
    }

    public function test_agendamento_esconde_servicos_de_prestador_em_revisao(): void
    {
        $servico = Servico::factory()->create(['nome' => 'Serviço Bloqueado']);
        $servico->prestador->prestadorProfile()->create(['em_revisao' => true]);

        $this->actingAs(User::factory()->create(['role' => 'cliente']))
            ->get('/cliente/agendar')->assertOk()->assertDontSee('Serviço Bloqueado');
    }

    public function test_historico_do_cliente_pesquisa_por_servico_ou_prestador(): void
    {
        $cliente = User::factory()->create(['role' => 'cliente']);
        foreach (['Pintura de parede', 'Troca de chuveiro'] as $nome) {
            $servico = Servico::factory()->create(['nome' => $nome]);
            Agendamento::factory()->create(['cliente_id' => $cliente->id, 'prestador_id' => $servico->prestador_id, 'servico_id' => $servico->id]);
        }

        $this->actingAs($cliente)->get('/cliente/historico?busca=chuveiro')->assertOk()
            ->assertSee('Troca de chuveiro')->assertDontSee('Pintura de parede');
    }

    public function test_historico_avisa_a_multa_antes_de_cancelar(): void
    {
        $cliente = User::factory()->create(['role' => 'cliente']);
        Agendamento::factory()->create([
            'cliente_id' => $cliente->id,
            'status' => 'confirmado',
            'data_hora' => now()->addHours(10),
            'preco_acordado' => 150.00,
        ]);

        $this->actingAs($cliente)->get('/cliente/historico')->assertOk()
            ->assertSee('multa de R$ 30,00', false); // 20% de R$ 150,00
    }

    public function test_cliente_cancela_tarde_e_a_multa_aparece_no_historico(): void
    {
        $cliente = User::factory()->create(['role' => 'cliente']);
        $agendamento = Agendamento::factory()->create([
            'cliente_id' => $cliente->id,
            'status' => 'pendente',
            'data_hora' => now()->addHours(3),
            'preco_acordado' => 100.00,
        ]);

        $this->actingAs($cliente)->patch("/cliente/agendamentos/{$agendamento->id}/cancelar")->assertSessionHas('success');

        $this->assertEquals(20.00, $agendamento->fresh()->cancelamento->multa);
        $this->get('/cliente/historico')->assertSee('Cancelado com menos de 24h: multa de R$ 20,00');
    }

    public function test_admin_altera_a_multa_de_cancelamento(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/configuracoes')->assertOk()->assertSee('Multa de cancelamento');

        $this->put('/admin/configuracoes/multa', ['multa_cancelamento' => '15'])->assertSessionHas('success');
        $this->assertSame(15.0, Configuracao::multaCancelamento());

        $this->put('/admin/configuracoes/multa', ['multa_cancelamento' => '150'])->assertSessionHasErrors('multa_cancelamento');
        $this->assertSame(15.0, Configuracao::multaCancelamento());
    }

    public function test_admin_ve_alerta_e_conclui_revisao(): void
    {
        $prestador = User::factory()->create(['role' => 'prestador', 'name' => 'Prestador Avaliado']);
        // Prazo de 30 dias vencido sem a rotina diária: o dashboard já mostra o alerta.
        $prestador->prestadorProfile()->create(['abaixo_media_desde' => now()->subDays(31)]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()
            ->assertSee('Prestador Avaliado')->assertSee('Concluir revisão');

        $this->patch("/admin/prestadores/{$prestador->id}/revisao", ['observacao' => 'Ok'])->assertSessionHas('success');
        $this->assertFalse($prestador->prestadorProfile->fresh()->em_revisao);
    }

    public function test_rotina_diaria_de_reputacao(): void
    {
        $prestador = User::factory()->create(['role' => 'prestador']);
        $prestador->prestadorProfile()->create(['abaixo_media_desde' => now()->subDays(30)]);

        $this->artisan('reputacao:verificar')->expectsOutputToContain('Prestadores colocados em revisão: 1')->assertSuccessful();
        $this->assertTrue($prestador->prestadorProfile->fresh()->em_revisao);
    }

    public function test_agendamento_tem_pesquisa_de_servico_na_categoria(): void
    {
        $servico = Servico::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'cliente']))
            ->get('/cliente/agendar')->assertOk()
            ->assertSee('data-busca-servico', false)
            ->assertSee('Pesquisar serviço em '.$servico->categoria->nome_categoria);
    }

    public function test_servico_gratis_aparece_como_gratis_e_nao_gera_cobranca(): void
    {
        $servico = Servico::factory()->create(['nome' => 'Orçamento sem custo', 'preco_sugerido' => 0]);
        $cliente = User::factory()->create(['role' => 'cliente']);

        $this->actingAs($cliente)->get('/cliente/agendar')->assertOk()->assertSee('Grátis');

        // Concluído: sem recebimento. Cancelado em cima da hora: sem multa.
        $concluido = Agendamento::factory()->create([
            'cliente_id' => $cliente->id, 'prestador_id' => $servico->prestador_id, 'servico_id' => $servico->id,
            'status' => 'confirmado', 'preco_acordado' => 0,
        ]);
        \App\Services\AgendamentoService::concluir($concluido, $servico->prestador_id);
        $this->assertDatabaseMissing('recebimentos', ['agendamento_id' => $concluido->id]);

        $tardio = Agendamento::factory()->create([
            'cliente_id' => $cliente->id, 'prestador_id' => $servico->prestador_id, 'servico_id' => $servico->id,
            'status' => 'confirmado', 'preco_acordado' => 0, 'data_hora' => now()->addHours(2),
        ]);
        $this->assertNull(\App\Services\AgendamentoService::cancelar($tardio, $cliente->id)->cancelamento->multa);
    }
}
