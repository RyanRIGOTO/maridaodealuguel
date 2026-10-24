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
        return [
            // Atalhos de teste (não são colunas: cliente e prestador vêm do agendamento).
            // Usados para criar o agendamento certo e descartados em newModel().
            'cliente_id' => null,
            'prestador_id' => null,
            'agendamento_id' => fn (array $attributes) => \App\Models\Agendamento::factory()->concluido()->create(array_filter([
                'cliente_id' => $attributes['cliente_id'],
                'prestador_id' => $attributes['prestador_id'],
            ]))->id,
            'nota' => fake()->numberBetween(1, 5),
            'comentario' => fake()->optional()->paragraph(),
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

    public function newModel(array $attributes = [])
    {
        unset($attributes['cliente_id'], $attributes['prestador_id']);

        return parent::newModel($attributes);
    }
}
