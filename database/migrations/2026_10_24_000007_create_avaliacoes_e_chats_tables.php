<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cliente e prestador avaliados saem do agendamento (3FN); a data é created_at.
        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->unique()->constrained('agendamentos')->restrictOnDelete();
            $table->unsignedTinyInteger('nota'); // 1 a 5
            $table->text('comentario')->nullable();
            $table->boolean('moderada')->default(false); // RN9
            $table->timestamps();
        });

        // Mensagens do agendamento. O destinatário é sempre a outra parte (cliente ou
        // prestador), então só o remetente é guardado. A data é created_at.
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->constrained('agendamentos')->restrictOnDelete();
            $table->foreignId('remetente_id')->constrained('users')->restrictOnDelete();
            $table->text('mensagem');
            $table->foreignId('proposta_horario_id')->nullable()->unique()->constrained('propostas_horario')->restrictOnDelete();
            $table->boolean('lido')->default(false);
            $table->timestamps();

            $table->index(['agendamento_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
        Schema::dropIfExists('avaliacoes');
    }
};
