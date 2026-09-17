<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Agendamento;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agendamento>
 */
class AgendamentoFactory extends Factory
{
    protected $model = Agendamento::class;

    public function definition(): array
    {
        $cliente = \App\Models\User::factory()->state(['role' => 'cliente']);
        $prestador = \App\Models\User::factory()->state(['role' => 'prestador']);
        $servico = \App\Models\Servico::factory()->for($prestador);

        return [
            'cliente_id' => $cliente,
            'prestador_id' => $prestador,
            'servico_id' => $servico,
            'data_hora' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'endereco_servico' => fake()->address(),
            'status' => fake()->randomElement(['pendente', 'confirmado', 'concluido', 'cancelado']),
            'preco_acordado' => fake()->randomFloat(2, 50, 500),
        ];
    }

    public function pendente(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pendente',
        ]);
    }

    public function confirmado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmado',
        ]);
    }

    public function concluido(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'concluido',
        ]);
    }

    public function cancelado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelado',
        ]);
    }

    public function passado(): static
    {
        return $this->state(fn (array $attributes) => [
            'data_hora' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'status' => 'concluido',
        ]);
    }

    public function futuro(): static
    {
        return $this->state(fn (array $attributes) => [
            'data_hora' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'status' => fake()->randomElement(['pendente', 'confirmado']),
        ]);
    }
}