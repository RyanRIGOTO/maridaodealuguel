<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome_categoria', 100)->unique();
            $table->enum('status', ['ativo', 'inativo'])->default('ativo');
            $table->timestamps();
        });

        // RESTRICT: categoria ou prestador com serviços não pode ser apagado (inativa-se).
        Schema::create('servicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->foreignId('prestador_id')->constrained('users')->restrictOnDelete();
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->decimal('preco_sugerido', 10, 2); // 0 = serviço grátis
            $table->enum('status', ['ativo', 'inativo'])->default('ativo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicos');
        Schema::dropIfExists('categorias');
    }
};
