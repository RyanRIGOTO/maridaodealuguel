<?php

namespace Tests\Feature;

use App\Mail\CodigoRecuperacaoSenha;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RecuperacaoSenhaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function pedirCodigo(User $user): string
    {
        $this->post('/esqueci-senha', ['email' => $user->email])
            ->assertRedirect(route('password.code'));

        $codigo = null;
        Mail::assertSent(CodigoRecuperacaoSenha::class, function ($mail) use ($user, &$codigo) {
            $codigo = $mail->codigo;

            return $mail->hasTo($user->email);
        });

        return $codigo;
    }

    public function test_paginas_de_recuperacao_carregam(): void
    {
        $this->get('/login')->assertSee(route('password.request'));
        $this->get('/esqueci-senha')->assertOk()->assertSee('Enviar código');

        // Sem passar pela primeira etapa, as outras voltam ao início.
        $this->get('/esqueci-senha/codigo')->assertRedirect(route('password.request'));
        $this->get('/esqueci-senha/nova-senha')->assertRedirect(route('password.request'));
    }

    public function test_fluxo_completo_redefine_a_senha(): void
    {
        $user = User::factory()->create(['email' => 'ana@email.com']);
        $codigo = $this->pedirCodigo($user);

        $reset = PasswordResetCode::where('user_id', $user->id)->first();
        $this->assertNotSame($codigo, $reset->code, 'O código deve ser salvo como hash.');
        $this->assertTrue(Hash::check($codigo, $reset->code));

        $this->post('/esqueci-senha/codigo', ['code' => $codigo])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.reset'));

        $this->post('/esqueci-senha/nova-senha', [
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('novaSenha123', $user->fresh()->password));
        $this->assertNotNull($reset->fresh()->used_at);
        $this->assertDatabaseHas('audit_logs', ['acao' => 'senha_redefinida', 'entidade_id' => $user->id]);

        $this->post('/login', ['email' => $user->email, 'password' => 'novaSenha123'])
            ->assertRedirect(route('cliente.dashboard'));
    }

    public function test_email_nao_cadastrado_recebe_a_mesma_resposta(): void
    {
        $this->post('/esqueci-senha', ['email' => 'ninguem@email.com'])
            ->assertRedirect(route('password.code'))
            ->assertSessionHas('success');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('password_reset_codes', 0);
    }

    public function test_conta_inativa_nao_recebe_codigo(): void
    {
        $user = User::factory()->create(['status' => 'inativo']);

        $this->post('/esqueci-senha', ['email' => $user->email])->assertRedirect(route('password.code'));

        Mail::assertNothingSent();
    }

    public function test_codigo_errado_conta_tentativas_ate_bloquear(): void
    {
        $user = User::factory()->create();
        $codigo = $this->pedirCodigo($user);
        $errado = $codigo === '111111' ? '222222' : '111111';

        for ($i = 1; $i <= PasswordResetService::MAX_TENTATIVAS; $i++) {
            $this->post('/esqueci-senha/codigo', ['code' => $errado])->assertSessionHasErrors('code');
        }

        // Mesmo o código certo deixa de valer depois do limite.
        $this->post('/esqueci-senha/codigo', ['code' => $codigo])->assertSessionHasErrors([
            'code' => 'Número de tentativas excedido. Solicite um novo código.',
        ]);
    }

    public function test_codigo_expirado_nao_vale(): void
    {
        $user = User::factory()->create();
        $codigo = $this->pedirCodigo($user);

        $this->travel(PasswordResetService::VALIDADE_MINUTOS + 1)->minutes();

        $this->post('/esqueci-senha/codigo', ['code' => $codigo])->assertSessionHasErrors([
            'code' => 'Este código expirou. Solicite um novo.',
        ]);
    }

    public function test_novo_pedido_invalida_o_codigo_anterior(): void
    {
        $user = User::factory()->create();
        $primeiro = $this->pedirCodigo($user);

        $this->post('/esqueci-senha', ['email' => $user->email]);

        $this->assertSame(1, PasswordResetCode::whereNull('used_at')->count());

        if ($primeiro !== Mail::sent(CodigoRecuperacaoSenha::class)->last()->codigo) {
            $this->post('/esqueci-senha/codigo', ['code' => $primeiro])->assertSessionHasErrors('code');
        }
    }

    public function test_codigo_nao_pode_ser_usado_duas_vezes(): void
    {
        $user = User::factory()->create();
        $codigo = $this->pedirCodigo($user);

        $this->post('/esqueci-senha/codigo', ['code' => $codigo]);
        $this->post('/esqueci-senha/nova-senha', [
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ]);

        $this->withSession(['recuperacao.email' => $user->email])
            ->post('/esqueci-senha/codigo', ['code' => $codigo])
            ->assertSessionHasErrors('code');
    }

    public function test_nova_senha_precisa_de_confirmacao_e_tamanho_minimo(): void
    {
        $user = User::factory()->create();
        $this->post('/esqueci-senha/codigo', ['code' => $this->pedirCodigo($user)]);

        $this->post('/esqueci-senha/nova-senha', [
            'password' => 'curta',
            'password_confirmation' => 'outra',
        ])->assertSessionHasErrors('password');

        $this->assertNull(PasswordResetCode::first()->used_at);
    }
}
