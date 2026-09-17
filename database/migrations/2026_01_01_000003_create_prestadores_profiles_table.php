<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestadores_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('endereco_completo', 255)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('area_atuacao', 100)->nullable();
            $table->json('disponibilidade_horarios')->nullable();
            $table->decimal('reputacao_media', 3, 2)->default(5.00);
            $table->boolean('em_revisao')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestadores_profiles');
    }
};