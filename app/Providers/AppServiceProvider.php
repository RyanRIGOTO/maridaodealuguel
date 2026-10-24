<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra os serviços da aplicação.
     */
    public function register(): void
    {
        // Meio de pagamento: troque PagamentoSimulado pela classe do gateway real.
        $this->app->bind(\App\Pagamentos\GatewayPagamento::class, \App\Pagamentos\PagamentoSimulado::class);
    }

    /**
     * Inicializa os serviços da aplicação.
     */
    public function boot(): void
    {
        //
    }
}
