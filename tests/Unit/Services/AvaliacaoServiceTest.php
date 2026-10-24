<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AvaliacaoService;
use App\Models\User;
use App\Models\Servico;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use Illuminate\Validation\ValidationException;

class AvaliacaoServiceTest extends TestCase
{
    protected User $cliente;
    protected User $prestador;
    protected User $admin;
    protected Servico $servico;
    protected Agendamento $agendamentoConcluido;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cliente = User::factory()->create([
            'role' => 'cliente',
            'status' => 'ativo',
        ]);

        $this->prestador = User::factory()->create([
            'role' => 'prestador',
            'status' => 'ativo',
        ]);

        $this->prestador->prestadorProfile()->create(['area_atuacao' => 'São Paulo']);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'ativo',
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
            'data_hora' => now()->subDays(2),
            'preco_acordado' => 100.00,
        ]);
    }

    public function test_criar_avaliacao_rn8_rn10_sucesso(): void
    {
        $avaliacao = AvaliacaoService::criar(
            $this->agendamentoConcluido->id,
            $this->cliente->id,
            5,
            'Excelente serviço!'
        );

        $this->assertInstanceOf(Avaliacao::class, $avaliacao);
        $this->assertEquals(5, $avaliacao->nota);
        $this->assertEquals('Excelente serviço!', $avaliacao->comentario);
        $this->assertEquals($this->cliente->id, $avaliacao->agendamento->cliente_id);
        $this->assertEquals($this->prestador->id, $avaliacao->prestador_id);
        $this->assertFalse($avaliacao->fresh()->moderada);

        // RN10: Reputação atualizada
        $this->prestador->refresh();
        $this->assertEquals(5.00, $this->prestador->prestadorProfile->reputacao_media);
        $this->assertFalse($this->prestador->prestadorProfile->em_revisao);

        // RN14: Auditoria
        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'avaliacao_criada',
            'entidade' => 'avaliacoes',
            'entidade_id' => $avaliacao->id,
        ]);
    }

    public function test_criar_avaliacao_falha_nao_e_dono(): void
    {
        $outroCliente = User::factory()->create(['role' => 'cliente']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Você só pode avaliar seus próprios agendamentos.');

        AvaliacaoService::criar($this->agendamentoConcluido->id, $outroCliente->id, 5, 'Comentário');
    }

    public function test_criar_avaliacao_falha_nao_concluido(): void
    {
        $agendamentoPendente = Agendamento::factory()->create([
            'cliente_id' => $this->cliente->id,
            'prestador_id' => $this->prestador->id,
            'servico_id' => $this->servico->id,
            'status' => 'pendente',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Apenas serviços concluídos podem ser avaliados.');

        AvaliacaoService::criar($agendamentoPendente->id, $this->cliente->id, 5, 'Comentário');
    }

    public function test_criar_avaliacao_falha_ja_avaliado(): void
    {
        // Cria primeira avaliação
        Avaliacao::factory()->create([
            'agendamento_id' => $this->agendamentoConcluido->id,
            'cliente_id' => $this->cliente->id,
            'prestador_id' => $this->prestador->id,
            'nota' => 4,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Este agendamento já foi avaliado.');

        AvaliacaoService::criar($this->agendamentoConcluido->id, $this->cliente->id, 5, 'Comentário');
    }

    public function test_atualizar_reputacao_rn10_media_alta(): void
    {
        // Cria múltiplas avaliações altas
        Avaliacao::factory()->count(3)->create([
            'prestador_id' => $this->prestador->id,
            'nota' => 5,
            'moderada' => false,
        ]);

        AvaliacaoService::atualizarReputacao($this->prestador->id);

        $this->prestador->refresh();
        $this->assertEquals(5.00, $this->prestador->prestadorProfile->reputacao_media);
        $this->assertFalse($this->prestador->prestadorProfile->em_revisao);
    }

    public function test_atualizar_reputacao_rn10_media_baixa_so_entra_em_revisao_apos_30_dias(): void
    {
        // Cria avaliações com média < 2.5
        Avaliacao::factory()->count(3)->create([
            'prestador_id' => $this->prestador->id,
            'nota' => 2,
            'moderada' => false,
        ]);

        AvaliacaoService::atualizarReputacao($this->prestador->id);

        $perfil = $this->prestador->prestadorProfile->fresh();
        $this->assertEquals(2.00, $perfil->reputacao_media);
        $this->assertNotNull($perfil->abaixo_media_desde); // começou a contar
        $this->assertFalse($perfil->em_revisao);           // mas ainda não entrou em revisão

        // 30 dias depois, ainda abaixo da média: a rotina diária coloca em revisão.
        $this->travel(30)->days();
        $this->assertSame(1, AvaliacaoService::verificarRevisoesPendentes());
        $this->assertTrue($perfil->fresh()->em_revisao);

        // RN14: Auditoria de prestador em revisão
        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'prestador_em_revisao',
            'entidade' => 'users',
            'entidade_id' => $this->prestador->id,
        ]);
    }

    public function test_atualizar_reputacao_rn10_recuperar_a_media_zera_o_prazo(): void
    {
        Avaliacao::factory()->create(['prestador_id' => $this->prestador->id, 'nota' => 1, 'moderada' => false]);
        AvaliacaoService::atualizarReputacao($this->prestador->id);
        $this->assertNotNull($this->prestador->prestadorProfile->fresh()->abaixo_media_desde);

        Avaliacao::factory()->count(3)->create(['prestador_id' => $this->prestador->id, 'nota' => 5, 'moderada' => false]);
        AvaliacaoService::atualizarReputacao($this->prestador->id);

        $this->travel(31)->days();
        $this->assertSame(0, AvaliacaoService::verificarRevisoesPendentes());
        $perfil = $this->prestador->prestadorProfile->fresh();
        $this->assertNull($perfil->abaixo_media_desde);
        $this->assertFalse($perfil->em_revisao);
    }

    public function test_concluir_revisao_libera_e_reinicia_contagem_se_media_segue_baixa(): void
    {
        $perfil = $this->prestador->prestadorProfile;
        // Média baixa (2,0) vem das avaliações; a reputação não é gravada.
        Avaliacao::factory()->count(2)->create(['prestador_id' => $this->prestador->id, 'nota' => 2, 'moderada' => false]);
        $perfil->update(['em_revisao' => true, 'abaixo_media_desde' => now()->subDays(40)]);

        AvaliacaoService::concluirRevisao($perfil, 1, 'Conversamos com o prestador.');

        $perfil->refresh();
        $this->assertFalse($perfil->em_revisao);
        $this->assertTrue($perfil->abaixo_media_desde->isToday());
        $this->assertDatabaseHas('audit_logs', ['acao' => 'revisao_prestador_concluida', 'entidade_id' => $this->prestador->id]);
    }

    public function test_atualizar_reputacao_ignora_moderadas(): void
    {
        Avaliacao::factory()->create([
            'prestador_id' => $this->prestador->id,
            'nota' => 5,
            'moderada' => false,
        ]);

        Avaliacao::factory()->create([
            'prestador_id' => $this->prestador->id,
            'nota' => 1,
            'moderada' => true, // Moderada, deve ser ignorada
        ]);

        AvaliacaoService::atualizarReputacao($this->prestador->id);

        $this->prestador->refresh();
        // Média deve ser 5.0 (apenas a não moderada conta)
        $this->assertEquals(5.00, $this->prestador->prestadorProfile->reputacao_media);
    }

    public function test_moderar_avaliacao_rn9_aprova(): void
    {
        $avaliacao = Avaliacao::factory()->create([
            'prestador_id' => $this->prestador->id,
            'nota' => 2,
            'moderada' => false,
        ]);

        $resultado = AvaliacaoService::moderar($avaliacao, true, 'Aprovada após análise', $this->admin->id);

        $this->assertTrue($resultado->moderada);
        $this->assertDatabaseHas('audit_logs', [
            'acao' => 'avaliacao_moderada',
            'entidade_id' => $avaliacao->id,
            'detalhes->admin_id' => $this->admin->id,
            'detalhes->moderada' => true,
        ]);

        // Reputação recalculada após moderação
        $this->prestador->refresh();
        $this->assertEquals(5.00, $this->prestador->prestadorProfile->reputacao_media); // Valor padrão sem avaliações não moderadas
    }

    public function test_moderar_avaliacao_rn9_rejeita(): void
    {
        $avaliacao = Avaliacao::factory()->create([
            'prestador_id' => $this->prestador->id,
            'nota' => 1,
            'moderada' => false,
        ]);

        $resultado = AvaliacaoService::moderar($avaliacao, false, 'Spam detectado', $this->admin->id);

        // Se moderada = false (rejeitada), a avaliação continua contando? 
        // Pelo código: update(['moderada' => $moderada]) - se false, continua não moderada
        $this->assertFalse($resultado->moderada);
    }
}
