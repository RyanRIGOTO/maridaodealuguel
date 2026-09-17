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
        $valorTotal = fake()->randomFloat(2, 50, 500);
        $taxaAdmin = round($valorTotal * 0.10, 2);
        $valorLiquido = $valorTotal - $taxaAdmin;

        return [
            'agendamento_id' => $agendamento,
            'valor_total' => $valorTotal,
            'taxa_admin' => $taxaAdmin,
            'valor_liquido_prestador' => $valorLiquido,
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