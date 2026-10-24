<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pessoa que usa o sistema (cliente, prestador ou administrador).
     * Dados pessoais comuns a todos os papéis ficam aqui, uma única vez.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->string('cpf_cnpj', 14)->unique(); // só dígitos/letras, sem máscara
            $table->date('data_nascimento')->nullable(); // opcional só para o administrador
            $table->enum('role', ['cliente', 'prestador', 'admin'])->default('cliente');
            $table->enum('status', ['ativo', 'inativo'])->default('ativo');
            $table->timestamp('termos_aceitos_em')->nullable(); // RN11: quando aceitou os termos (LGPD)
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
