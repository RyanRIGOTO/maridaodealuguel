<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Recebimento;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recebimento>
 */
class RecebimentoFactory extends Factory
{
    protected $model = Recebimento::class;

    public function definition(): array
    {
        $agendamento = \App\Models\Agendamento::factory()->concluido();
        return [
            'agendamento_id' => $agendamento,
            'valor_total' => fake()->randomFloat(2, 50, 500),
            'percentual_taxa' => 10.00,
            'status_recebimento' => fake()->randomElement(['pendente', 'pago']),
            'data_liberacao' => now()->addHours(48),
        ];
    }

    public function pago(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_recebimento' => 'pago',
            'data_liberacao' => now(),
        ]);
    }

    public function pendente(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_recebimento' => 'pendente',
            'data_liberacao' => now()->addHours(48),
        ]);
    }
}