<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Endereco>
 */
class EnderecoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'cep' => fake()->numerify('########'),
            'logradouro' => 'Rua '.fake()->lastName(),
            'numero' => (string) fake()->numberBetween(1, 2000),
            'complemento' => null,
            'bairro' => 'Centro',
            'cidade' => 'Umuarama',
            'uf' => 'PR',
            'principal' => false,
        ];
    }

    public function principal(): static
    {
        return $this->state(['principal' => true]);
    }
}
