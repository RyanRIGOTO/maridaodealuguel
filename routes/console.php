<?php

use App\Services\AvaliacaoService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// RN10: coloca em revisão os prestadores com média abaixo de 2,5 há 30 dias seguidos.
// Em produção, o cron do servidor precisa chamar "php artisan schedule:run" a cada minuto.
Artisan::command('reputacao:verificar', function () {
    $total = AvaliacaoService::verificarRevisoesPendentes();
    $this->info("Prestadores colocados em revisão: {$total}");
})->purpose('Aplica a RN10 (revisão após 30 dias com nota média baixa)');

Schedule::command('reputacao:verificar')->dailyAt('03:00');
