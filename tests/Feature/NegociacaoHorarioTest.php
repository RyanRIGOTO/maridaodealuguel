<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Chat;
use App\Models\PropostaHorario;
use App\Models\User;
use Tests\TestCase;

class NegociacaoHorarioTest extends TestCase
{
    private Agendamento $agendamento;

    protected function setUp(): void
    {
        parent::setUp();

        $prestador = User::factory()->create(['role' => 'prestador']);
        $this->agendamento = Agendamento::factory()->create([
            'cliente_id' => User::factory()->create(['role' => 'cliente'])->id,
            'prestador_id' => $prestador->id,
            'status' => 'confirmado',
            'data_hora' => now()->addDays(2)->setTime(10, 0),
        ]);
    }

    private function propor(string $hora = '15:00', ?User $quem = null)
    {
        return $this->actingAs($quem ?? $this->agendamento->prestador)
            ->from('/prestador/agendamentos')
            ->post("/prestador/agendamentos/{$this->agendamento->id}/propor-horario", [
                'data' => now()->addDays(3)->toDateString(),
                'hora' => $hora,
            ]);
    }

    private function responder(PropostaHorario $proposta, string $resposta)
    {
        return $this->actingAs($this->agendamento->cliente)->patch("/chat/propostas/{$proposta->id}", ['resposta' => $resposta]);
    }

    public function test_prestador_propoe_e_cliente_aceita(): void
    {
        $this->propor()->assertRedirect(route('chat.index', ['agendamento' => $this->agendamento->id]));
        $proposta = PropostaHorario::where('status', 'pendente')->sole();

        // A data só muda depois do aceite.
        $this->assertSame('10:00', $this->agendamento->fresh()->data_hora->format('H:i'));
        $this->actingAs($this->agendamento->cliente)->get('/chat?agendamento='.$this->agendamento->id)
            ->assertSee('Proposta de novo horário')->assertSee('Aceitar');

        $this->responder($proposta, 'aceitar')->assertSessionHas('success');

        $this->assertSame(now()->addDays(3)->setTime(15, 0)->toDateTimeString(), $this->agendamento->fresh()->data_hora->toDateTimeString());
        $this->assertSame('aceita', $proposta->fresh()->status);
        $this->assertDatabaseHas('chats', ['mensagem' => 'Aceitei o novo horário: '.now()->addDays(3)->format('d/m/Y').' às 15:00.']);
    }

    public function test_cliente_recusa_e_horario_continua(): void
    {
        $this->propor();
        $this->responder(PropostaHorario::where('status', 'pendente')->sole(), 'recusar')->assertSessionHas('success');

        $this->assertSame('10:00', $this->agendamento->fresh()->data_hora->format('H:i'));
        $this->assertDatabaseHas('propostas_horario', ['status' => 'recusada']);
    }

    public function test_proposta_nova_substitui_a_anterior_e_a_antiga_nao_pode_ser_aceita(): void
    {
        $this->propor('15:00');
        $antiga = PropostaHorario::where('status', 'pendente')->sole();
        $this->propor('16:00');

        $this->assertSame('substituida', $antiga->fresh()->status);
        $this->responder($antiga, 'aceitar')->assertSessionHas('error');
        $this->assertSame('10:00', $this->agendamento->fresh()->data_hora->format('H:i'));
    }

    public function test_so_o_prestador_do_agendamento_propoe_e_so_o_cliente_responde(): void
    {
        $this->propor('15:00', User::factory()->create(['role' => 'prestador']))->assertSessionHas('error');
        $this->assertDatabaseCount('chats', 0);

        $this->propor();
        $proposta = PropostaHorario::where('status', 'pendente')->sole();
        // O próprio prestador não pode aceitar a proposta que enviou.
        $this->actingAs($this->agendamento->prestador)->patch("/chat/propostas/{$proposta->id}", ['resposta' => 'aceitar'])
            ->assertSessionHas('error');
        $this->assertSame('pendente', $proposta->fresh()->status);
    }

    public function test_proposta_respeita_conflito_de_agenda(): void
    {
        // Prestador já tem outro atendimento no horário proposto.
        Agendamento::factory()->create([
            'prestador_id' => $this->agendamento->prestador_id,
            'status' => 'confirmado',
            'data_hora' => now()->addDays(3)->setTime(15, 30),
        ]);

        $this->propor('15:00')->assertSessionHas('error');
        $this->assertDatabaseCount('chats', 0);
    }

    public function test_remarcar_no_mesmo_dia_nao_conflita_com_ele_mesmo(): void
    {
        $this->actingAs($this->agendamento->prestador)
            ->post("/prestador/agendamentos/{$this->agendamento->id}/propor-horario", [
                'data' => now()->addDays(2)->toDateString(),
                'hora' => '11:00', // 1h depois do próprio horário atual
            ])->assertSessionHas('success');
    }
}
