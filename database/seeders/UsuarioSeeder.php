<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Uso updateOrCreate para que o seeder seja idempotente
        Usuario::updateOrCreate(
            ['cpf' => '11122233344'],
            ['nome' => 'Ana Silva', 'telefone' => '(11) 99999-0001', 'endereco' => 'Rua A, 123, São Paulo', 'email' => 'ana@example.com', 'senha' => Hash::make('senha123')]
        );

        Usuario::updateOrCreate(
            ['cpf' => '22233344455'],
            ['nome' => 'Bruno Costa', 'telefone' => '(11) 99999-0002', 'endereco' => 'Av B, 45, São Paulo', 'email' => 'bruno@example.com', 'senha' => Hash::make('minhaSenha')]
        );

        Usuario::updateOrCreate(
            ['cpf' => '33344455566'],
            ['nome' => 'Carla Pereira', 'telefone' => '(11) 99999-0003', 'endereco' => 'Travessa C, 7, Campinas', 'email' => 'carla@example.com', 'senha' => Hash::make('abc12345')]
        );

        Usuario::updateOrCreate(
            ['cpf' => '44455566677'],
            ['nome' => 'Daniel Almeida', 'telefone' => '(11) 99999-0004', 'endereco' => 'Praça D, 10, Santos', 'email' => 'daniel@example.com', 'senha' => Hash::make('passw0rd')]
        );

        Usuario::updateOrCreate(
            ['cpf' => '55566677788'],
            ['nome' => 'Eduarda Rocha', 'telefone' => '(11) 99999-0005', 'endereco' => 'Rua E, 88, Ribeirão', 'email' => 'eduarda@example.com', 'senha' => Hash::make('segura!')]
        );
    }
}
