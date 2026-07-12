<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\BilletRepositoryInterface;
use App\Repositories\Eloquent\BilletRepository;
use App\Repositories\Contracts\VoyageRepositoryInterface;
use App\Repositories\Eloquent\VoyageRepository;
use App\Repositories\Contracts\PayementRepositoryInterface;
use App\Repositories\Eloquent\PayementRepository;
use App\Repositories\Contracts\PortefeuilleRepositoryInterface;
use App\Repositories\Eloquent\PortefeuilleRepository;
use App\Repositories\Contracts\AbonnementRepositoryInterface;
use App\Repositories\Eloquent\AbonnementRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Enregistrer les liaisons de dépôts (repositories) dans le conteneur de services.
     */
    public function register(): void
    {
        // Liaison pour la gestion des billets
        $this->app->bind(BilletRepositoryInterface::class, BilletRepository::class);
        
        // Liaison pour la gestion des voyages
        $this->app->bind(VoyageRepositoryInterface::class, VoyageRepository::class);
        
        // Liaison pour la gestion des paiements
        $this->app->bind(PayementRepositoryInterface::class, PayementRepository::class);
        
        // Liaison pour la gestion des portefeuilles numériques
        $this->app->bind(PortefeuilleRepositoryInterface::class, PortefeuilleRepository::class);
        
        // Liaison pour la gestion des abonnements
        $this->app->bind(AbonnementRepositoryInterface::class, AbonnementRepository::class);
    }

    /**
     * Initialiser les services de l'application.
     */
    public function boot(): void
    {
        //
    }
}
