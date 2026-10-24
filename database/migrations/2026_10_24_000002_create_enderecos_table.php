<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Endereços em campos atômicos (1FN). Um usuário pode ter vários; o principal
     * é o do cadastro. Agendamentos apontam para o endereço do atendimento.
     */
    public function up(): void
    {
        Schema::create('enderecos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->char('cep', 8)->nullable();
            $table->string('logradouro', 75);
            $table->string('numero', 10);
            $table->string('complemento', 35)->nullable();
            $table->string('bairro', 45);
            $table->string('cidade', 45);
            $table->char('uf', 2);
            $table->boolean('principal')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'principal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enderecos');
    }
};
