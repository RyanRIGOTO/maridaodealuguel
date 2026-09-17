<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recebimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->constrained('agendamentos')->onDelete('cascade');
            $table->decimal('valor_total', 10, 2);
            $table->decimal('taxa_admin', 10, 2);
            $table->decimal('valor_liquido_prestador', 10, 2);
            $table->enum('status_recebimento', ['pendente', 'pago', 'cancelado'])->default('pendente');
            $table->dateTime('data_liberacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recebimentos');
    }
};