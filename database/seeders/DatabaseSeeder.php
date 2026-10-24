<?php

namespace Database\Seeders;

use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Categoria;
use App\Models\Chat;
use App\Models\Configuracao;
use App\Models\Recebimento;
use App\Models\Servico;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Database\Seeder;

/**
 * Dados de demonstração. Rodar só em banco de desenvolvimento: php artisan db:seed
 * Senha de todos os usuários de demonstração: senha123 (admin e contas de teste: ver ContasTesteSeeder).
 */
class DatabaseSeeder extends Seeder
{
    private array $prestadores = [];
    private array $clientes = [];
    private array $servicos = [];
    private array $agendamentos = [];

    public function run(): void
    {
        $this->createCategorias();
        $this->createPrestadores();
        $this->createServicos();
        $this->createClientes();
        $this->createAgendamentos();
        $this->createRecebimentos();
        $this->createAvaliacoes();
        $this->createChats();

        $this->call(ContasTesteSeeder::class);

        $this->command->info('Banco populado com dados de demonstração.');
        $this->command->info('Prestadores: joao.silva@email.com, maria.santos@email.com, carlos.oliveira@email.com');
        $this->command->info('Clientes: ana.costa@email.com, pedro.almeida@email.com (senha de todos: senha123)');
    }

    /** CPF válido a partir de 9 dígitos (para os dados de demonstração passarem na validação). */
    public static function cpf(string $base): string
    {
        foreach ([10, 11] as $peso) {
            $soma = 0;
            foreach (str_split($base) as $i => $digito) {
                $soma += (int) $digito * ($peso - $i);
            }
            $resto = $soma % 11;
            $base .= $resto < 2 ? '0' : (string) (11 - $resto);
        }

        return $base;
    }

    private function createCategorias(): void
    {
        $nomes = ['Elétrica', 'Encanamento', 'Pintura', 'Marcenaria', 'Limpeza', 'Jardinagem', 'Ar Condicionado', 'Pequenos Reparos'];
        foreach ($nomes as $nome) {
            Categoria::create(['nome_categoria' => $nome, 'status' => 'ativo']);
        }
    }

    private function createPrestadores(): void
    {
        $prestadoresData = [
            ['name' => 'João Silva', 'email' => 'joao.silva@email.com', 'phone' => '(44) 98888-1111', 'cpf' => '111444777', 'nascimento' => '1985-03-15', 'area' => 'Centro e Zona Norte de Umuarama',
                'endereco' => ['87501000', 'Rua das Flores', '123', 'Centro'], 'dias' => [[1, '08:00', '18:00'], [2, '08:00', '18:00'], [3, '08:00', '18:00'], [4, '08:00', '18:00'], [5, '08:00', '17:00']]],
            ['name' => 'Maria Santos', 'email' => 'maria.santos@email.com', 'phone' => '(44) 97777-2222', 'cpf' => '222555888', 'nascimento' => '1990-07-22', 'area' => 'Umuarama e região',
                'endereco' => ['87502000', 'Avenida Paraná', '1000', 'Zona I'], 'dias' => [[1, '09:00', '18:00'], [3, '09:00', '18:00'], [5, '09:00', '17:00'], [6, '09:00', '13:00']]],
            ['name' => 'Carlos Oliveira', 'email' => 'carlos.oliveira@email.com', 'phone' => '(44) 96666-3333', 'cpf' => '333666999', 'nascimento' => '1982-11-10', 'area' => 'Zona Sul e Zona Oeste',
                'endereco' => ['87503000', 'Rua Ministro Oliveira Salazar', '500', 'Zona III'], 'dias' => [[1, '07:00', '16:00'], [2, '07:00', '16:00'], [4, '07:00', '16:00']]],
        ];

        foreach ($prestadoresData as $data) {
            $user = $this->criarUsuario($data, 'prestador');
            $perfil = $user->prestadorProfile()->create(['area_atuacao' => $data['area']]);

            foreach ($data['dias'] as [$dia, $inicio, $fim]) {
                $perfil->disponibilidades()->create(['dia_semana' => $dia, 'hora_inicio' => $inicio, 'hora_fim' => $fim]);
            }

            $this->prestadores[] = $user;
        }
    }

    private function createServicos(): void
    {
        $categoria = fn (string $nome) => Categoria::where('nome_categoria', $nome)->value('id');

        $servicosData = [
            [0, 'Elétrica', 'Instalação de Tomadas', 'Instalação e troca de tomadas e interruptores.', 80.00],
            [0, 'Elétrica', 'Troca de Disjuntores', 'Substituição de disjuntores com defeito.', 120.00],
            [0, 'Encanamento', 'Desentupimento de Pia', 'Desentupimento profissional de pias e ralos.', 100.00],
            [1, 'Encanamento', 'Conserto de Vazamentos', 'Identificação e reparo de vazamentos em tubulações.', 180.00],
            [1, 'Encanamento', 'Instalação de Torneiras', 'Instalação de torneiras e chuveiros.', 90.00],
            [1, 'Pintura', 'Pintura de Interiores', 'Pintura de paredes e tetos por cômodo.', 350.00],
            [2, 'Marcenaria', 'Montagem de Móveis', 'Montagem de guarda-roupas, camas, estantes.', 200.00],
            [2, 'Marcenaria', 'Conserto de Portas', 'Ajuste e conserto de portas e gavetas.', 80.00],
            [2, 'Limpeza', 'Limpeza Pós-Obra', 'Limpeza completa após reformas.', 250.00],
            [2, 'Pequenos Reparos', 'Orçamento no Local', 'Visita para avaliar o serviço e passar o orçamento.', 0.00], // grátis
        ];

        foreach ($servicosData as [$prestador, $nomeCategoria, $nome, $descricao, $preco]) {
            $this->servicos[] = Servico::create([
                'prestador_id' => $this->prestadores[$prestador]->id,
                'categoria_id' => $categoria($nomeCategoria),
                'nome' => $nome,
                'descricao' => $descricao,
                'preco_sugerido' => $preco,
                'status' => 'ativo',
            ]);
        }
    }

    private function createClientes(): void
    {
        $clientesData = [
            ['name' => 'Ana Costa', 'email' => 'ana.costa@email.com', 'phone' => '(44) 95555-4444', 'cpf' => '444777000', 'nascimento' => '1992-05-18',
                'endereco' => ['87504000', 'Rua dos Pinheiros', '200', 'Jardim América']],
            ['name' => 'Pedro Almeida', 'email' => 'pedro.almeida@email.com', 'phone' => '(44) 94444-5555', 'cpf' => '555888111', 'nascimento' => '1988-09-30',
                'endereco' => ['87505000', 'Avenida Rio Branco', '1500', 'Zona II']],
        ];

        foreach ($clientesData as $data) {
            $this->clientes[] = $this->criarUsuario($data, 'cliente');
        }
    }

    private function criarUsuario(array $data, string $role): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => 'senha123',
            'role' => $role,
            'cpf_cnpj' => self::cpf($data['cpf']),
            'data_nascimento' => $data['nascimento'],
            'termos_aceitos_em' => now(),
            'email_verified_at' => now(),
        ]);

        [$cep, $logradouro, $numero, $bairro] = $data['endereco'];
        $user->enderecos()->create([
            'cep' => $cep, 'logradouro' => $logradouro, 'numero' => $numero, 'bairro' => $bairro,
            'cidade' => 'Umuarama', 'uf' => 'PR', 'principal' => true,
        ]);

        AuditLogService::log('usuario_criado', 'users', $user->id, ['role' => $role]);

        return $user;
    }

    private function createAgendamentos(): void
    {
        $agendamentosData = [
            // [cliente, serviço, dias a partir de hoje, hora, status]
            [0, 0, 2, '10:00', 'confirmado'],
            [0, 3, 5, '14:00', 'pendente'],
            [0, 5, -3, '09:00', 'concluido'],
            [1, 6, 1, '08:00', 'confirmado'],
            [1, 7, -10, '15:00', 'concluido'],
        ];

        foreach ($agendamentosData as [$cliente, $servicoIdx, $dias, $hora, $status]) {
            $servico = $this->servicos[$servicoIdx];
            $clienteModel = $this->clientes[$cliente];
            [$h, $m] = array_map('intval', explode(':', $hora));

            $agendamento = Agendamento::create([
                'cliente_id' => $clienteModel->id,
                'servico_id' => $servico->id,
                'endereco_id' => $clienteModel->enderecoPrincipal->id,
                'data_hora' => now()->addDays($dias)->setTime($h, $m),
                'status' => $status,
                'preco_acordado' => $servico->preco_sugerido,
            ]);
            $this->agendamentos[] = $agendamento;
            AuditLogService::log('agendamento_criado', 'agendamentos', $agendamento->id, ['status' => $status]);
        }
    }

    private function createRecebimentos(): void
    {
        // Mesma taxa configurada pelo administrador (RN7).
        $percentual = Configuracao::taxaPlataforma();

        foreach ($this->agendamentos as $i => $agendamento) {
            if ($agendamento->status !== 'concluido') {
                continue;
            }

            $recebimento = Recebimento::create([
                'agendamento_id' => $agendamento->id,
                'tipo' => 'servico',
                'valor_total' => $agendamento->preco_acordado,
                'percentual_taxa' => $percentual,
                'status_recebimento' => $i === 2 ? 'pago' : 'pendente',
                'data_liberacao' => $agendamento->updated_at->addHours(48),
            ]);
            AuditLogService::log('recebimento_criado', 'recebimentos', $recebimento->id, ['agendamento_id' => $agendamento->id]);
        }
    }

    private function createAvaliacoes(): void
    {
        $avaliacoesData = [
            [2, 5, 'Excelente trabalho! Maria foi muito profissional, pontual e deixou tudo muito limpo. Recomendo!'],
            [4, 4, 'Carlos fez um bom trabalho no conserto, chegou no horário combinado.'],
        ];

        foreach ($avaliacoesData as [$idx, $nota, $comentario]) {
            $avaliacao = Avaliacao::create([
                'agendamento_id' => $this->agendamentos[$idx]->id,
                'nota' => $nota,
                'comentario' => $comentario,
            ]);
            AuditLogService::log('avaliacao_criada', 'avaliacoes', $avaliacao->id, ['nota' => $nota]);
        }
    }

    private function createChats(): void
    {
        foreach ($this->agendamentos as $agendamento) {
            if (!in_array($agendamento->status, ['confirmado', 'concluido'])) {
                continue;
            }

            $prestadorId = $agendamento->prestador_id;
            $mensagens = [
                [$agendamento->cliente_id, 'Olá! Tudo bem? Confirmando o agendamento.'],
                [$prestadorId, 'Olá! Tudo certo, estarei aí no horário.'],
                [$agendamento->cliente_id, 'Perfeito, obrigado!'],
            ];

            foreach ($mensagens as $i => [$remetente, $texto]) {
                $chat = new Chat(['agendamento_id' => $agendamento->id, 'remetente_id' => $remetente, 'mensagem' => $texto, 'lido' => true]);
                $chat->created_at = $agendamento->created_at->addMinutes($i * 5);
                $chat->save();
            }
        }
    }
}
