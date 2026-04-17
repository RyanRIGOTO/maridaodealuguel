<?php

namespace Database\Seeders;

use App\Models\ClientePrestador;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Cria 5 clientes/prestadores de teste
        ClientePrestador::factory(5)->create();

        
        ClientePrestador::create([
            'name' => 'Ryan Rigoto',
            'email' => 'ryan@teste.com',
            'password' => bcrypt('12345678'),
        ]);
        
    }
}
