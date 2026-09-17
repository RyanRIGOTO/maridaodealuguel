<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->nullable()->constrained('agendamentos')->onDelete('set null');
            $table->foreignId('remetente_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('destinatario_id')->constrained('users')->onDelete('cascade');
            $table->text('mensagem');
            $table->dateTime('data_hora');
            $table->boolean('lido')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};