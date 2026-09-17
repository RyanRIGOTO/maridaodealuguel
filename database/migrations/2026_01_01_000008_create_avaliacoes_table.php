<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->unique()->constrained('agendamentos')->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('prestador_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('nota');
            $table->text('comentario')->nullable();
            $table->dateTime('data_avaliacao');
            $table->boolean('moderada')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};