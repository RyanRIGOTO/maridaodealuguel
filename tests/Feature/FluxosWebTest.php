<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Categoria;
use App\Models\Servico;
use App\Models\User;
use Tests\TestCase;

class FluxosWebTest extends TestCase
{
    public function test_paginas_publicas_carregam(): void
    {
        foreach (['/', '/login', '/cadastro/cliente', '/cadastro/prestador'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_agendamento_mostra_servicos_sem_expor_dados_pessoais_do_prestador(): void
    {
        $cliente = User::factory()->create(['role' => 'cliente']);
        $servico = Servico::factory()->create(['nome' => 'Instalação <segura>']);
        $servicoInativo = Servico::factory()->create(['status' => 'inativo']);

        $this->actingAs($cliente)->get('/cliente/agendar')
            ->assertOk()
            ->assertSee('Instalação &lt;segura&gt;', false)
            ->assertDontSee($servico->prestador->email)
            ->assertDontSee($servico->prestador->cpf_cnpj)
            ->assertDontSee($servicoInativo->nome);
    }

    public function test_agendamento_sem_servicos_mostra_estado_vazio(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'cliente']))
            ->get('/cliente/agendar')->assertOk()
            ->assertSee('Nenhum serviço disponível no momento.');
    }

    public function test_formulario_de_agendamento_salva_o_pedido(): void
    {
        $cliente = User::factory()->create(['role' => 'cliente']);
        $servico = Servico::factory()->create();

        // "Em outro endereço": o endereço digitado vira um registro em enderecos.
        $this->actingAs($cliente)->post('/cliente/agendar', [
            'servico_id' => $servico->id,
            'data' => now()->addDays(3)->toDateString(),
            'hora' => '10:00',
            'endereco_opcao' => 'outro',
            'cep' => '87500-000', 'logradouro' => 'Rua de Teste', 'numero' => '123',
            'bairro' => 'Centro', 'cidade' => 'Umuarama', 'uf' => 'pr',
        ])->assertSessionHasNoErrors()->assertRedirect(route('cliente.historico'));

        $endereco = \App\Models\Endereco::where('user_id', $cliente->id)->sole();
        $this->assertSame(['87500000', 'PR'], [$endereco->cep, $endereco->uf]);
        $this->assertDatabaseHas('agendamentos', [
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'endereco_id' => $endereco->id,
            'status' => 'pendente',
        ]);
    }

    public function test_agendamento_rejeitado_devolve_os_campos_preenchidos(): void
    {
        $cliente = User::factory()->create(['role' => 'cliente']);
        $servico = Servico::factory()->create(['status' => 'inativo']);

        $this->actingAs($cliente)->from('/cliente/agendar')->post('/cliente/agendar', [
            'servico_id' => $servico->id,
            'data' => now()->addDays(3)->toDateString(),
            'hora' => '10:00',
            'endereco_opcao' => 'outro',
            'logradouro' => 'Rua de Teste', 'numero' => '123', 'bairro' => 'Centro', 'cidade' => 'Umuarama', 'uf' => 'PR',
        ])->assertRedirect('/cliente/agendar')->assertSessionHasErrors('servico_id')
            ->assertSessionHasInput('logradouro', 'Rua de Teste');

        // Agendamento recusado não deixa endereço solto no banco.
        $this->assertDatabaseCount('enderecos', 0);
    }

    public function test_cliente_visualiza_e_envia_avaliacao(): void
    {
        $agendamento = Agendamento::factory()->concluido()->create();
        $this->actingAs($agendamento->cliente)->get('/cliente/avaliacoes')
            ->assertOk()->assertSee($agendamento->servico->nome);

        $this->from('/cliente/avaliacoes')->post('/cliente/avaliacoes/'.$agendamento->id, [
            'nota' => '4',
            'comentario' => 'Bom atendimento',
        ])->assertSessionHasNoErrors()->assertRedirect('/cliente/avaliacoes');

        $this->assertDatabaseHas('avaliacoes', ['agendamento_id' => $agendamento->id, 'nota' => 4]);
    }

    public function test_prestador_visualiza_e_edita_servico(): void
    {
        $servico = Servico::factory()->create();
        $this->actingAs($servico->prestador)->get('/prestador/servicos')
            ->assertOk()->assertSee($servico->nome)
            ->assertSee('data-combobox', false) // campo único de pesquisa/escolha de categoria
            ->assertSee($servico->categoria->nome_categoria);

        $this->from('/prestador/servicos')->patch('/prestador/servicos/'.$servico->id, [
            'nome' => 'Serviço atualizado',
            'descricao' => 'Descrição atualizada',
            'categoria_id' => $servico->categoria_id,
            'preco_sugerido' => '150.00',
            'status' => 'ativo',
        ])->assertSessionHasNoErrors()->assertRedirect('/prestador/servicos');

        $this->assertDatabaseHas('servicos', ['id' => $servico->id, 'nome' => 'Serviço atualizado']);
    }

    public function test_administrador_visualiza_e_edita_categoria(): void
    {
        $categoria = Categoria::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/categorias')->assertOk()->assertSee($categoria->nome_categoria);

        $this->from('/admin/categorias')->patch('/admin/categorias/'.$categoria->id, [
            'nome_categoria' => 'Categoria atualizada',
            'status' => 'ativo',
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/categorias');

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'nome_categoria' => 'Categoria atualizada']);
    }
}
