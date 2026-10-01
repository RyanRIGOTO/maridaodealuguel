<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CadastroDocumentoTest extends TestCase
{
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
        $mensagem = 'Informe um CPF ou CNPJ válido, ou tente outro CPF/CNPJ.';
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
}
