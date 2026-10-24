<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 100);
            $table->string('entidade', 100);
            $table->unsignedBigInteger('entidade_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('detalhes')->nullable(); // contexto livre de cada ação (RN14)
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entidade', 'entidade_id']);
        });

        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code'); // hash do código, nunca o código puro
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'used_at']);
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        // Valores iniciais das configurações do admin (RN4 e RN7).
        DB::table('configuracoes')->insert([
            ['chave' => 'taxa_plataforma', 'valor' => '5.00', 'created_at' => now(), 'updated_at' => now()],
            ['chave' => 'multa_cancelamento', 'valor' => '50.00', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('password_reset_codes');
        Schema::dropIfExists('audit_logs');
    }
};
