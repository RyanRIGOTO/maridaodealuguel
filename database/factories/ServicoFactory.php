<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Servico;
use App\Models\Categoria;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Servico>
 */
class ServicoFactory extends Factory
{
    protected $model = Servico::class;

    public function definition(): array
    {
        return [
            'categoria_id' => Categoria::factory(),
            'prestador_id' => \App\Models\User::factory()->state(['role' => 'prestador']),
            'nome' => fake()->words(3, true),
            'descricao' => fake()->paragraph(),
            'preco_sugerido' => fake()->randomFloat(2, 50, 500),
            'status' => 'ativo',
        ];
    }
}