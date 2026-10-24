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

        return [
            'cliente_id' => $cliente,
            // Atalho de teste: não é coluna (o prestador vem do serviço). Serve para criar
            // o serviço do prestador certo e é descartado em newModel().
            'prestador_id' => $prestador,
            'servico_id' => fn (array $attributes) => \App\Models\Servico::factory()->create([
                'prestador_id' => $attributes['prestador_id'],
            ])->id,
            'endereco_id' => fn (array $attributes) => \App\Models\Endereco::factory()->create([
                'user_id' => $attributes['cliente_id'],
            ])->id,
            'data_hora' => fake()->dateTimeBetween('+1 day', '+30 days'),
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

    public function newModel(array $attributes = [])
    {
        unset($attributes['prestador_id']);

        return parent::newModel($attributes);
    }
}
