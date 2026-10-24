<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Categoria;
use App\Models\Servico;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Falhas encontradas na revisão de segurança e consistência (21/10/2026).
 */
class SegurancaTest extends TestCase
{
    private function agendamento(string $status = 'pendente'): Agendamento
    {
        $prestador = User::factory()->create(['role' => 'prestador']);
        $servico = Servico::factory()->create(['prestador_id' => $prestador->id]);

        return Agendamento::factory()->create([
            'cliente_id' => User::factory()->create(['role' => 'cliente'])->id,
            'prestador_id' => $prestador->id,
            'servico_id' => $servico->id,
            'status' => $status,
            'data_hora' => now()->addDays(3),
        ]);
    }

    public function test_api_nao_deixa_terceiro_cancelar_agendamento(): void
    {
        $agendamento = $this->agendamento();
        Sanctum::actingAs(User::factory()->create(['role' => 'cliente']));

        $this->postJson("/api/agendamentos/{$agendamento->id}/cancelar")->assertUnprocessable();
        $this->assertSame('pendente', $agendamento->fresh()->status);
    }

    public function test_api_publica_nao_expoe_dados_pessoais_do_prestador(): void
    {
        $prestador = User::factory()->create(['role' => 'prestador', 'cpf_cnpj' => '52998224725', 'phone' => '(44) 99999-0000']);
        $prestador->prestadorProfile()->create(['area_atuacao' => 'Centro']);
        $prestador->enderecos()->create(['logradouro' => 'Rua Secreta', 'numero' => '1', 'bairro' => 'Centro', 'cidade' => 'Umuarama', 'uf' => 'PR', 'principal' => true]);
        Servico::factory()->create(['prestador_id' => $prestador->id]);

        $resposta = $this->getJson("/api/prestadores/{$prestador->id}")->assertOk();

        foreach (['52998224725', '99999-0000', 'Rua Secreta', $prestador->email] as $dado) {
            $this->assertStringNotContainsString($dado, $resposta->getContent());
        }
    }

    public function test_usuario_ve_os_proprios_dados_pessoais(): void
    {
        $cliente = User::factory()->create(['role' => 'cliente', 'cpf_cnpj' => '52998224725']);
        Sanctum::actingAs($cliente);

        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.cpf_cnpj', '52998224725');
    }

    public function test_cliente_nao_cadastra_servico_pela_api(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'cliente']));

        $this->postJson('/api/servicos', [
            'categoria_id' => Categoria::factory()->create()->id,
            'nome' => 'Serviço falso',
            'descricao' => 'Teste',
            'preco_sugerido' => 10,
        ])->assertForbidden();
    }

    public function test_api_chat_envia_sempre_para_a_outra_parte(): void
    {
        $agendamento = $this->agendamento();
        $intruso = User::factory()->create();
        Sanctum::actingAs($agendamento->cliente);

        $this->postJson('/api/chat', [
            'agendamento_id' => $agendamento->id,
            'destinatario_id' => $intruso->id,
            'mensagem' => 'Olá',
        ])->assertCreated()->assertJsonPath('data.destinatario_id', $agendamento->prestador_id);
    }

    public function test_usuario_inativado_perde_a_sessao_web(): void
    {
        $cliente = User::factory()->create(['role' => 'cliente']);
        $this->actingAs($cliente)->get('/cliente/dashboard')->assertOk();

        $cliente->update(['status' => 'inativo']);

        $this->get('/cliente/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_token_de_usuario_inativado_deixa_de_valer(): void
    {
        Sanctum::actingAs(User::factory()->create(['status' => 'inativo']));

        $this->getJson('/api/auth/me')->assertForbidden();
    }

    public function test_admin_nao_inativa_outro_administrador(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $outroAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch("/admin/usuarios/{$outroAdmin->id}/status");

        $this->assertSame('ativo', $outroAdmin->fresh()->status);
    }

    public function test_excluir_categoria_com_servicos_e_bloqueado_para_preservar_historico(): void
    {
        $agendamento = $this->agendamento('concluido');
        $categoria = $agendamento->servico->categoria;

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete("/admin/categorias/{$categoria->id}")
            ->assertSessionHas('error');

        $this->assertModelExists($categoria);
        $this->assertModelExists($agendamento);
    }

    public function test_excluir_servico_com_agendamentos_e_bloqueado(): void
    {
        $agendamento = $this->agendamento('concluido');

        $this->actingAs($agendamento->prestador)
            ->delete("/prestador/servicos/{$agendamento->servico_id}")
            ->assertSessionHas('error');

        $this->assertModelExists($agendamento);
    }

    public function test_login_tem_limite_de_tentativas(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'errada']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'errada'])->assertStatus(429);
    }

    public function test_agendamento_web_rejeita_horario_que_ja_passou(): void
    {
        $prestador = User::factory()->create(['role' => 'prestador']);
        $servico = Servico::factory()->create(['prestador_id' => $prestador->id, 'status' => 'ativo']);

        $this->actingAs(User::factory()->create(['role' => 'cliente']))
            ->from('/cliente/agendar')
            ->post('/cliente/agendar', [
                'servico_id' => $servico->id,
                'data' => now()->toDateString(),
                'hora' => now()->subHour()->format('H:i'),
                'endereco_opcao' => 'outro',
                'logradouro' => 'Rua de Teste', 'numero' => '123', 'bairro' => 'Centro', 'cidade' => 'Umuarama', 'uf' => 'PR',
            ])->assertSessionHasErrors('data_hora');
    }

    public function test_verificar_conflito_da_api_nao_quebra(): void
    {
        $prestador = User::factory()->create(['role' => 'prestador']);
        Sanctum::actingAs(User::factory()->create(['role' => 'cliente']));

        // A regra 'datetime' não existe no Laravel e fazia essa rota responder 500.
        $this->postJson('/api/agendamentos/verificar-conflito', [
            'prestador_id' => $prestador->id,
            'data_hora' => now()->addDays(2)->toDateTimeString(),
        ])->assertOk()->assertJsonPath('data.conflito', false);
    }

    public function test_login_usa_a_mesma_mensagem_para_qualquer_falha(): void
    {
        // São 6 tentativas; o limite de tentativas tem teste próprio.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $ativo = User::factory()->create();
        $inativo = User::factory()->create(['status' => 'inativo']);
        $tentativas = [
            ['email' => 'nao.existe@example.test', 'password' => 'password'],
            ['email' => $ativo->email, 'password' => 'errada'],
            ['email' => $inativo->email, 'password' => 'password'], // senha certa, conta inativa
        ];

        foreach ($tentativas as $dados) {
            $this->post('/login', $dados)->assertSessionHasErrors(['email' => 'Credenciais inválidas.']);
            $this->postJson('/api/auth/login', $dados)->assertJsonPath('errors.email.0', 'Credenciais inválidas.');
        }
        $this->assertGuest();
    }
}
