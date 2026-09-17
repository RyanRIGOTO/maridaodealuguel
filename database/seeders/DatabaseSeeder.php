<?php

namespace Database\Seeders;

use App\Models\Agendamento;
use App\Models\AuditLog;
use App\Models\Avaliacao;
use App\Models\Categoria;
use App\Models\Chat;
use App\Models\Recebimento;
use App\Models\Servico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private array $prestadores = [];
    private array $clientes = [];
    private array $servicos = [];
    private array $agendamentos = [];

    public function run(): void
    {
        $this->createAdmin();
        $this->createCategorias();
        $this->createPrestadores();
        $this->createServicos();
        $this->createClientes();
        $this->createAgendamentos();
        $this->createRecebimentos();
        $this->createAvaliacoes();
        $this->createChats();

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin: admin@maridaodealuguel.com.br / senha123');
        $this->command->info('Prestador 1: joao.silva@email.com / senha123');
        $this->command->info('Prestador 2: maria.santos@email.com / senha123');
        $this->command->info('Prestador 3: carlos.oliveira@email.com / senha123');
        $this->command->info('Cliente 1: ana.costa@email.com / senha123');
        $this->command->info('Cliente 2: pedro.almeida@email.com / senha123');
    }

    private function createAdmin(): void
    {
        $admin = User::create([
            'name' => 'Administrador do Sistema',
            'email' => 'admin@maridaodealuguel.com.br',
            'phone' => '(11) 99999-0000',
            'password' => Hash::make('senha123'),
            'role' => 'admin',
            'status' => 'ativo',
            'cpf_cnpj' => '000.000.000-00',
            'termos_lgpd' => true,
            'email_verified_at' => now(),
        ]);
        AuditLog::log('usuario_criado', 'users', $admin->id, ['role' => 'admin']);
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
            ['name' => 'João Silva', 'email' => 'joao.silva@email.com', 'phone' => '(11) 98888-1111', 'cpf_cnpj' => '111.222.333-44', 'endereco' => 'Rua das Flores, 123 - São Paulo/SP', 'nascimento' => '1985-03-15', 'area' => 'Zona Sul e Centro de SP', 'disponibilidade' => ['seg' => ['08:00-18:00'], 'ter' => ['08:00-18:00'], 'qua' => ['08:00-18:00'], 'qui' => ['08:00-18:00'], 'sex' => ['08:00-17:00']]],
            ['name' => 'Maria Santos', 'email' => 'maria.santos@email.com', 'phone' => '(11) 97777-2222', 'cpf_cnpj' => '222.333.444-55', 'endereco' => 'Av. Paulista, 1000 - São Paulo/SP', 'nascimento' => '1990-07-22', 'area' => 'Grande São Paulo', 'disponibilidade' => ['seg' => ['09:00-18:00'], 'ter' => ['09:00-18:00'], 'qua' => ['09:00-18:00'], 'qui' => ['09:00-18:00'], 'sex' => ['09:00-17:00'], 'sab' => ['09:00-13:00']]],
            ['name' => 'Carlos Oliveira', 'email' => 'carlos.oliveira@email.com', 'phone' => '(11) 96666-3333', 'cpf_cnpj' => '333.444.555-66', 'endereco' => 'Rua Augusta, 500 - São Paulo/SP', 'nascimento' => '1982-11-10', 'area' => 'Zona Oeste e Zona Norte', 'disponibilidade' => ['seg' => ['07:00-16:00'], 'ter' => ['07:00-16:00'], 'qua' => ['07:00-16:00'], 'qui' => ['07:00-16:00'], 'sex' => ['07:00-15:00']]],
        ];
        foreach ($prestadoresData as $data) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
                'password' => Hash::make('password123'), 'role' => 'prestador', 'status' => 'ativo',
                'cpf_cnpj' => $data['cpf_cnpj'], 'termos_lgpd' => true, 'email_verified_at' => now(),
            ]);
            $user->prestadorProfile()->create([
                'endereco_completo' => $data['endereco'], 'data_nascimento' => $data['nascimento'],
                'area_atuacao' => $data['area'], 'disponibilidade_horarios' => $data['disponibilidade'],
                'reputacao_media' => 5.00, 'em_revisao' => false,
            ]);
            $this->prestadores[] = $user;
            AuditLog::log('usuario_criado', 'users', $user->id, ['role' => 'prestador']);
        }
    }

    private function createServicos(): void
    {
        $catEletrica = Categoria::where('nome_categoria', 'Elétrica')->first();
        $catEncanamento = Categoria::where('nome_categoria', 'Encanamento')->first();
        $catPintura = Categoria::where('nome_categoria', 'Pintura')->first();
        $catMarcenaria = Categoria::where('nome_categoria', 'Marcenaria')->first();
        $catLimpeza = Categoria::where('nome_categoria', 'Limpeza')->first();

        $servicosData = [
            [$this->prestadores[0]->id, $catEletrica->id, 'Instalação de Tomadas', 'Instalação e troca de tomadas e interruptores.', 80.00],
            [$this->prestadores[0]->id, $catEletrica->id, 'Troca de Disjuntores', 'Substituição de disjuntores com defeito.', 120.00],
            [$this->prestadores[0]->id, $catEncanamento->id, 'Desentupimento de Pia', 'Desentupimento profissional de pias e ralos.', 100.00],
            [$this->prestadores[1]->id, $catEncanamento->id, 'Conserto de Vazamentos', 'Identificação e reparo de vazamentos em tubulações.', 180.00],
            [$this->prestadores[1]->id, $catEncanamento->id, 'Instalação de Torneiras', 'Instalação de torneiras e chuveiros.', 90.00],
            [$this->prestadores[1]->id, $catPintura->id, 'Pintura de Interiores', 'Pintura de paredes e tetos por cômodo.', 350.00],
            [$this->prestadores[2]->id, $catMarcenaria->id, 'Montagem de Móveis', 'Montagem de guarda-roupas, camas, estantes.', 200.00],
            [$this->prestadores[2]->id, $catMarcenaria->id, 'Conserto de Portas', 'Ajuste e conserto de portas e gavetas.', 80.00],
            [$this->prestadores[2]->id, $catLimpeza->id, 'Limpeza Pós-Obra', 'Limpeza completa após reformas.', 250.00],
        ];

        foreach ($servicosData as $data) {
            $this->servicos[] = Servico::create([
                'prestador_id' => $data[0], 'categoria_id' => $data[1],
                'nome' => $data[2], 'descricao' => $data[3],
                'preco_sugerido' => $data[4], 'status' => 'ativo',
            ]);
        }
    }

    private function createClientes(): void
    {
        $clientesData = [
            ['name' => 'Ana Costa', 'email' => 'ana.costa@email.com', 'phone' => '(11) 95555-4444', 'cpf_cnpj' => '444.555.666-77', 'endereco' => 'Rua dos Pinheiros, 200 - São Paulo/SP', 'nascimento' => '1992-05-18'],
            ['name' => 'Pedro Almeida', 'email' => 'pedro.almeida@email.com', 'phone' => '(11) 94444-5555', 'cpf_cnpj' => '555.666.777-88', 'endereco' => 'Av. Brigadeiro Faria Lima, 1500 - São Paulo/SP', 'nascimento' => '1988-09-30'],
        ];
        foreach ($clientesData as $data) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
                'password' => Hash::make('password123'), 'role' => 'cliente', 'status' => 'ativo',
                'cpf_cnpj' => $data['cpf_cnpj'], 'termos_lgpd' => true, 'email_verified_at' => now(),
            ]);
            $user->clienteProfile()->create([
                'endereco_completo' => $data['endereco'], 'data_nascimento' => $data['nascimento'],
            ]);
            $this->clientes[] = $user;
        }
    }

    private function createAgendamentos(): void
    {
        $agendamentosData = [
            ['cliente_idx' => 0, 'servico_idx' => 0, 'data' => '2 days 10:00', 'endereco' => 'Rua dos Pinheiros, 200', 'status' => 'confirmado'],
            ['cliente_idx' => 0, 'servico_idx' => 3, 'data' => '5 days 14:00', 'endereco' => 'Rua dos Pinheiros, 200', 'status' => 'pendente'],
            ['cliente_idx' => 0, 'servico_idx' => 5, 'data' => '-3 days 09:00', 'endereco' => 'Rua dos Pinheiros, 200', 'status' => 'concluido'],
            ['cliente_idx' => 1, 'servico_idx' => 6, 'data' => '1 days 08:00', 'endereco' => 'Av. Faria Lima, 1500', 'status' => 'confirmado'],
            ['cliente_idx' => 1, 'servico_idx' => 7, 'data' => '-10 days 15:00', 'endereco' => 'Av. Faria Lima, 1500', 'status' => 'concluido'],
        ];

        foreach ($agendamentosData as $data) {
            $servico = $this->servicos[$data['servico_idx']];
            $parts = explode(' ', $data['data']);
            $days = (int)$parts[0];
            $timeString = $parts[2];
            [$hour, $minute] = array_map('intval', explode(':', $timeString));

            if ($days < 0) {
                $dt = now()->subDays(abs($days))->setTime($hour, $minute);
            } else {
                $dt = now()->addDays($days)->setTime($hour, $minute);
            }

            $agendamento = Agendamento::create([
                'cliente_id' => $this->clientes[$data['cliente_idx']]->id,
                'prestador_id' => $servico->prestador_id,
                'servico_id' => $servico->id,
                'data_hora' => $dt,
                'endereco_servico' => $data['endereco'],
                'status' => $data['status'],
                'preco_acordado' => $servico->preco_sugerido,
            ]);
            $this->agendamentos[] = $agendamento;
            AuditLog::log('agendamento_criado', 'agendamentos', $agendamento->id, ['status' => $data['status']]);
        }
    }

    private function createRecebimentos(): void
    {
        foreach ($this->agendamentos as $agendamento) {
            if ($agendamento->status === 'concluido') {
                $valorTotal = $agendamento->preco_acordado;
                $taxaAdmin = round($valorTotal * 0.10, 2);
                $valorLiquido = $valorTotal - $taxaAdmin;
                $liberacao = $agendamento->updated_at->addHours(48);

                $statusRecebimento = $agendamento->id == $this->agendamentos[2]->id ? 'pago' : 'pendente';
                Recebimento::create([
                    'agendamento_id' => $agendamento->id,
                    'valor_total' => $valorTotal, 'taxa_admin' => $taxaAdmin,
                    'valor_liquido_prestador' => $valorLiquido, 'status_recebimento' => $statusRecebimento,
                    'data_liberacao' => $liberacao,
                ]);
                AuditLog::log('recebimento_criado', 'recebimentos', null, ['agendamento_id' => $agendamento->id]);
            }
        }
    }

    private function createAvaliacoes(): void
    {
        $avaliacoesData = [
            ['agendamento_idx' => 2, 'nota' => 5, 'comentario' => 'Excelente trabalho! Maria foi muito profissional, pontual e deixou tudo muito limpo. Recomendo!'],
            ['agendamento_idx' => 4, 'nota' => 4, 'comentario' => 'Carlos fez um bom trabalho na instalação, chegou no horário combinado.'],
        ];

        foreach ($avaliacoesData as $data) {
            $agendamento = $this->agendamentos[$data['agendamento_idx']];
            Avaliacao::create([
                'agendamento_id' => $agendamento->id,
                'cliente_id' => $agendamento->cliente_id,
                'prestador_id' => $agendamento->prestador_id,
                'nota' => $data['nota'], 'comentario' => $data['comentario'],
                'data_avaliacao' => now()->subDays(rand(1, 3)),
                'moderada' => false,
            ]);
            $this->atualizarReputacao($agendamento->prestador_id);
            AuditLog::log('avaliacao_criada', 'avaliacoes', null, ['agendamento_id' => $agendamento->id, 'nota' => $data['nota']]);
        }
    }

    private function createChats(): void
    {
        foreach ($this->agendamentos as $agendamento) {
            if (in_array($agendamento->status, ['confirmado', 'concluido'])) {
                $msgs = [
                    [$agendamento->cliente_id, $agendamento->prestador_id, 'Olá! Tudo bem? Confirmando o agendamento.'],
                    [$agendamento->prestador_id, $agendamento->cliente_id, 'Olá! Tudo certo, estarei aí no horário.'],
                    [$agendamento->cliente_id, $agendamento->prestador_id, 'Perfeito, obrigado!'],
                ];
                foreach ($msgs as $i => $msg) {
                    Chat::create([
                        'agendamento_id' => $agendamento->id,
                        'remetente_id' => $msg[0], 'destinatario_id' => $msg[1],
                        'mensagem' => $msg[2],
                        'data_hora' => $agendamento->created_at->addMinutes($i * 5),
                        'lido' => true,
                    ]);
                }
            }
        }
    }

    private function atualizarReputacao(int $prestadorId): void
    {
        $media = Avaliacao::where('prestador_id', $prestadorId)->where('moderada', false)->avg('nota');
        $user = User::find($prestadorId);
        if ($user && $user->prestadorProfile) {
            $user->prestadorProfile->update([
                'reputacao_media' => round($media ?? 5.00, 2),
                'em_revisao' => ($media !== null && $media < 2.5),
            ]);
        }
    }
}