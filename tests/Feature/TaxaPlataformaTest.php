<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Configuracao;
use App\Models\Recebimento;
use App\Models\User;
use App\Services\RecebimentoService;
use Tests\TestCase;

class TaxaPlataformaTest extends TestCase
{
    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_taxa_padrao_e_cinco_por_cento(): void
    {
        $this->assertSame(5.0, Configuracao::taxaPlataforma());
    }

    public function test_admin_ve_e_altera_a_taxa(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/configuracoes')->assertOk()->assertSee('Taxa da plataforma');

        $this->from('/admin/configuracoes')->put('/admin/configuracoes/taxa', ['taxa_plataforma' => '12.5'])
            ->assertRedirect('/admin/configuracoes')->assertSessionHas('success');

        $this->assertSame(12.5, Configuracao::taxaPlataforma());
        $this->assertDatabaseHas('audit_logs', ['acao' => 'taxa_plataforma_alterada', 'usuario_id' => $admin->id]);
    }

    public function test_taxa_fora_do_intervalo_e_rejeitada(): void
    {
        $this->actingAs($this->admin());

        foreach (['-1', '100.01', 'abc', '5.555', ''] as $valor) {
            $this->from('/admin/configuracoes')->put('/admin/configuracoes/taxa', ['taxa_plataforma' => $valor])
                ->assertSessionHasErrors('taxa_plataforma');
        }

        $this->assertSame(5.0, Configuracao::taxaPlataforma());
    }

    public function test_quem_nao_e_admin_nao_altera_a_taxa(): void
    {
        foreach (['cliente', 'prestador'] as $papel) {
            $this->actingAs(User::factory()->create(['role' => $papel]))
                ->put('/admin/configuracoes/taxa', ['taxa_plataforma' => '50'])
                ->assertRedirect(route('home'));
        }

        $this->assertSame(5.0, Configuracao::taxaPlataforma());
    }

    public function test_recebimento_usa_a_taxa_configurada_e_guarda_o_percentual(): void
    {
        Configuracao::definir(Configuracao::TAXA_PLATAFORMA, '15.00');
        $agendamento = Agendamento::factory()->create(['status' => 'concluido', 'preco_acordado' => 200.00]);

        $recebimento = RecebimentoService::criar($agendamento);

        $this->assertEquals(30.00, $recebimento->taxa_admin);
        $this->assertEquals(170.00, $recebimento->valor_liquido_prestador);
        $this->assertEquals(15.00, $recebimento->percentual_taxa);
    }

    public function test_mudar_a_taxa_nao_altera_recebimentos_antigos(): void
    {
        $antigo = RecebimentoService::criar(Agendamento::factory()->create(['status' => 'concluido', 'preco_acordado' => 100.00]));

        $this->actingAs($this->admin())->put('/admin/configuracoes/taxa', ['taxa_plataforma' => '20']);

        $antigo = Recebimento::find($antigo->id);
        $this->assertEquals(5.00, $antigo->taxa_admin);
        $this->assertEquals(5.00, $antigo->percentual_taxa);
    }
}
