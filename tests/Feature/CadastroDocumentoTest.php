<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CadastroDocumentoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Os testes enviam vários cadastros seguidos; o limite de tentativas tem teste próprio.
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public static function rotas(): array
    {
        return [
            ['/cadastro/cliente', false],
            ['/cadastro/prestador', false],
            ['/api/auth/register/cliente', true],
            ['/api/auth/register/prestador', true],
        ];
    }

    private function dados(mixed $documento): array
    {
        return [
            'name' => 'Pessoa de Teste',
            'email' => 'cadastro@example.test',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
            'cpf_cnpj' => $documento,
            'data_nascimento' => '1995-01-01',
            'termos_lgpd' => true,
        ];
    }

    #[DataProvider('cadastrosValidos')]
    public function test_cadastra_documento_valido_sem_mascara(string $rota, bool $api, string $documento, string $normalizado): void
    {
        $resposta = $api ? $this->postJson($rota, $this->dados($documento)) : $this->post($rota, $this->dados($documento));
        if ($api) {
            $resposta->assertCreated();
        } else {
            $resposta->assertSessionHasNoErrors()->assertRedirect();
        }

        $this->assertDatabaseHas('users', ['email' => 'cadastro@example.test', 'cpf_cnpj' => $normalizado]);
    }

    public static function cadastrosValidos(): array
    {
        $casos = [];
        foreach (self::rotas() as [$rota, $api]) {
            foreach ([['012.345.678-90', '01234567890'], ['11.222.333/0001-81', '11222333000181'], ['12.abc.345/01de-35', '12ABC34501DE35']] as [$documento, $normalizado]) {
                $casos[] = [$rota, $api, $documento, $normalizado];
            }
        }

        return $casos;
    }

    #[DataProvider('rotas')]
    public function test_rejeita_documentos_invalidos_sem_criar_usuario(string $rota, bool $api): void
    {
        foreach (['52998224724', '11222333000180', '12ABC34501DE34', '00000000000', '00000000000000', '123', '52998224725!', null, ['52998224725'], 52998224725] as $documento) {
            $resposta = $api ? $this->postJson($rota, $this->dados($documento)) : $this->post($rota, $this->dados($documento));
            if ($api) {
                $resposta->assertUnprocessable()->assertJsonValidationErrors('cpf_cnpj');
            } else {
                $resposta->assertSessionHasErrors('cpf_cnpj');
            }
            $this->assertDatabaseMissing('users', ['email' => 'cadastro@example.test']);
        }
    }

    #[DataProvider('duplicados')]
    public function test_rejeita_duplicidade_inclusive_em_cadastros_antigos(string $rota, bool $api, string $salvo, string $enviado): void
    {
        User::factory()->create(['cpf_cnpj' => $salvo, 'role' => 'prestador']);
        $resposta = $api ? $this->postJson($rota, $this->dados($enviado)) : $this->post($rota, $this->dados($enviado));
        $mensagem = 'Não foi possível usar este CPF/CNPJ. Se você já tem conta, entre pela tela de login ou use "Esqueci minha senha".';
        if ($api) {
            $resposta->assertUnprocessable()->assertJsonPath('errors.cpf_cnpj.0', $mensagem);
        } else {
            $resposta->assertSessionHasErrors(['cpf_cnpj' => $mensagem]);
        }
        $this->assertDatabaseMissing('users', ['email' => 'cadastro@example.test']);
    }

    public static function duplicados(): array
    {
        $casos = [];
        foreach (self::rotas() as [$rota, $api]) {
            foreach ([['529.982.247-25', '52998224725'], ['52998224725', '529.982.247-25'], ['11.222.333/0001-81', '11222333000181'], ['12.abc.345/01de-35', '12ABC34501DE35'], ['12ABC34501DE35', '12.abc.345/01de-35']] as [$salvo, $enviado]) {
                $casos[] = [$rota, $api, $salvo, $enviado];
            }
        }

        return $casos;
    }

    public function test_mensagens_do_cadastro_sao_claras_e_em_portugues(): void
    {
        $this->from('/cadastro/cliente')->post('/cadastro/cliente', [
            'email' => 'sem-arroba',
            'password' => '123',
            'password_confirmation' => '999',
            'cpf_cnpj' => '123',
            'data_nascimento' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors([
            'name' => 'Informe seu nome completo.',
            'email' => 'E-mail inválido. Confira se está no formato nome@exemplo.com.',
            'password' => 'A senha precisa ter pelo menos 8 caracteres.',
            'cpf_cnpj' => 'CPF ou CNPJ inválido. Confira os números digitados.',
            'data_nascimento' => 'É preciso ter pelo menos 18 anos para se cadastrar.',
            'termos_lgpd' => 'Para criar a conta, aceite os Termos de Uso e a Política de Privacidade.',
        ]);
    }

    public function test_cadastro_exige_18_anos_completos(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $dados = fn (string $nascimento) => [...$this->dados('52998224725'), 'data_nascimento' => $nascimento];

        // Nasceu ontem / faz 18 amanhã: recusado.
        foreach ([now()->subDay(), now()->subYears(18)->addDay()] as $data) {
            $this->post('/cadastro/cliente', $dados($data->toDateString()))
                ->assertSessionHasErrors(['data_nascimento' => 'É preciso ter pelo menos 18 anos para se cadastrar.']);
        }

        // Faz 18 hoje: aceito.
        $this->post('/cadastro/cliente', $dados(now()->subYears(18)->toDateString()))->assertSessionHasNoErrors();
    }
}
