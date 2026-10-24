<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O prestador do agendamento é o dono do serviço (servicos.prestador_id); por isso
     * não há prestador_id aqui (3FN). preco_acordado é o preço no momento do pedido,
     * que pode diferir do preço atual do serviço.
     */
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->restrictOnDelete();
            $table->foreignId('endereco_id')->constrained('enderecos')->restrictOnDelete();
            $table->dateTime('data_hora');
            $table->enum('status', ['pendente', 'confirmado', 'concluido', 'cancelado'])->default('pendente');
            $table->decimal('preco_acordado', 10, 2);
            $table->timestamps();

            $table->index(['status', 'data_hora']); // RN3: busca de conflitos na agenda
        });

        // RN4: um cancelamento por agendamento; a multa é o valor cobrado do cliente.
        Schema::create('cancelamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->unique()->constrained('agendamentos')->restrictOnDelete();
            $table->foreignId('cancelado_por')->constrained('users')->restrictOnDelete();
            $table->decimal('multa', 10, 2)->nullable();
            $table->timestamps();
        });

        // Negociação: o prestador propõe um novo horário e o cliente aceita ou recusa.
        Schema::create('propostas_horario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->constrained('agendamentos')->restrictOnDelete();
            $table->dateTime('data_hora');
            $table->enum('status', ['pendente', 'aceita', 'recusada', 'substituida'])->default('pendente');
            $table->timestamps();

            $table->index(['agendamento_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propostas_horario');
        Schema::dropIfExists('cancelamentos');
        Schema::dropIfExists('agendamentos');
    }
};
