<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Contas para validar o sistema manualmente (login, agendamento, chat).
 * Rodar só em ambiente de desenvolvimento: php artisan db:seed --class=ContasTesteSeeder
 *
 * - Admin:     admin@maridaodealuguel.com.br / password123
 * - Cliente:   cliente.teste@maridao.test     / Teste@123
 * - Prestador: prestador.teste@maridao.test   / Teste@123
 */
class ContasTesteSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(['email' => 'admin@maridaodealuguel.com.br'], [
            'name' => 'Administrador do Sistema',
            'phone' => '(44) 99999-0000',
            'password' => 'password123',
            'role' => 'admin',
            'cpf_cnpj' => DatabaseSeeder::cpf('000111222'),
            'termos_aceitos_em' => now(),
            'email_verified_at' => now(),
        ]);

        $cliente = User::firstOrCreate(['email' => 'cliente.teste@maridao.test'], [
            'name' => 'Cliente Teste',
            'phone' => '(44) 99999-4321',
            'password' => 'Teste@123',
            'role' => 'cliente',
            'cpf_cnpj' => '39053344705',
            'data_nascimento' => '1995-03-20',
            'termos_aceitos_em' => now(),
        ]);
        $cliente->enderecos()->firstOrCreate(['principal' => true], [
            'cep' => '87501000', 'logradouro' => 'Rua do Cliente Teste', 'numero' => '200',
            'bairro' => 'Centro', 'cidade' => 'Umuarama', 'uf' => 'PR',
        ]);

        $prestador = User::firstOrCreate(['email' => 'prestador.teste@maridao.test'], [
            'name' => 'Prestador Teste',
            'phone' => '(44) 99999-1234',
            'password' => 'Teste@123',
            'role' => 'prestador',
            'cpf_cnpj' => '99746647059',
            'data_nascimento' => '1990-05-10',
            'termos_aceitos_em' => now(),
        ]);
        $prestador->enderecos()->firstOrCreate(['principal' => true], [
            'cep' => '87501100', 'logradouro' => 'Rua de Teste', 'numero' => '100',
            'bairro' => 'Centro', 'cidade' => 'Umuarama', 'uf' => 'PR',
        ]);
        $prestador->prestadorProfile()->firstOrCreate([], ['area_atuacao' => 'Umuarama e região']);

        $categoria = Categoria::firstOrCreate(['nome_categoria' => 'Ar Condicionado'], ['status' => 'ativo']);
        Servico::firstOrCreate(['prestador_id' => $prestador->id, 'nome' => 'Serviço de teste'], [
            'categoria_id' => $categoria->id,
            'descricao' => 'Serviço criado para validação manual.',
            'preco_sugerido' => 120.00,
            'status' => 'ativo',
        ]);
    }
}
