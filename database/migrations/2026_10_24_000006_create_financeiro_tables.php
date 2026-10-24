<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pagamentos feitos pelo cliente (hoje: multa; futuro: o serviço).
        // O pagador é o cliente do agendamento, por isso não há user_id.
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->constrained('agendamentos')->restrictOnDelete();
            $table->enum('tipo', ['multa', 'servico']);
            $table->decimal('valor', 10, 2);
            $table->enum('metodo', ['pix', 'cartao', 'boleto']);
            $table->enum('status', ['aprovado', 'recusado']);
            $table->string('gateway', 30);
            $table->string('transacao_id', 100)->nullable()->unique();
            $table->dateTime('pago_em')->nullable();
            $table->timestamps();

            $table->index(['agendamento_id', 'tipo', 'status']);
        });

        // Repasses ao prestador. Taxa e valor líquido não são guardados: saem de
        // valor_total x percentual_taxa (percentual vigente quando foi gerado, RN7).
        Schema::create('recebimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->constrained('agendamentos')->restrictOnDelete();
            $table->enum('tipo', ['servico', 'multa'])->default('servico');
            $table->decimal('valor_total', 10, 2);
            $table->decimal('percentual_taxa', 5, 2);
            $table->enum('status_recebimento', ['pendente', 'pago', 'cancelado'])->default('pendente');
            $table->dateTime('data_liberacao')->nullable(); // RN6: +48h
            $table->timestamps();

            $table->unique(['agendamento_id', 'tipo']); // um repasse de cada tipo por agendamento
        });

        Schema::create('configuracoes', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 50)->unique();
            $table->string('valor', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
        Schema::dropIfExists('recebimentos');
        Schema::dropIfExists('pagamentos');
    }
};
