<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dados exclusivos do papel de prestador (relação 1:1 com users).
     * A reputação não é guardada: é a média das avaliações, calculada quando precisa.
     */
    public function up(): void
    {
        Schema::create('prestadores_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('area_atuacao', 100)->nullable();
            $table->boolean('em_revisao')->default(false);           // RN10
            $table->dateTime('abaixo_media_desde')->nullable();      // RN10: início dos 30 dias
            $table->timestamps();
        });

        // Disponibilidade semanal (antes era um JSON; agora uma linha por faixa de horário).
        Schema::create('disponibilidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestador_profile_id')->constrained('prestadores_profiles')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 0 = domingo ... 6 = sábado
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->timestamps();

            $table->unique(['prestador_profile_id', 'dia_semana', 'hora_inicio'], 'disponibilidades_faixa_unique'); // nome curto: limite de 64 caracteres do MySQL
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disponibilidades');
        Schema::dropIfExists('prestadores_profiles');
    }
};
