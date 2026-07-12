<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Enregistrer les services de l'application.
     */
    public function register(): void
    {
        // Associe le client PayDunya approprié (réel ou simulation) selon le mode configuré.
        $this->app->bind(\App\Services\Paiements\PayDunya\PayDunyaClientInterface::class, function ($app) {
            return config('services.paydunya.mode') === 'real'
                ? $app->make(\App\Services\Paiements\PayDunya\PayDunyaClient::class)
                : $app->make(\App\Services\Paiements\PayDunya\FakePayDunyaClient::class);
        });
    }

    /**
     * Initialiser les services de l'application.
     */
    public function boot(): void
    {
        //
    }
}
