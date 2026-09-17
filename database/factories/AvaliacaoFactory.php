<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Avaliacao;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Avaliacao>
 */
class AvaliacaoFactory extends Factory
{
    protected $model = Avaliacao::class;

    public function definition(): array
    {
        $agendamento = \App\Models\Agendamento::factory()->concluido();

        return [
            'agendamento_id' => $agendamento,
            'cliente_id' => $agendamento->cliente_id,
            'prestador_id' => $agendamento->prestador_id,
            'nota' => fake()->numberBetween(1, 5),
            'comentario' => fake()->optional()->paragraph(),
            'data_avaliacao' => now(),
            'moderada' => false,
        ];
    }

    public function moderada(): static
    {
        return $this->state(fn (array $attributes) => [
            'moderada' => true,
        ]);
    }

    public function baixa(): static
    {
        return $this->state(fn (array $attributes) => [
            'nota' => fake()->numberBetween(1, 2),
        ]);
    }

    public function alta(): static
    {
        return $this->state(fn (array $attributes) => [
            'nota' => fake()->numberBetween(4, 5),
        ]);
    }
}