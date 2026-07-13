<?php

namespace App\Listeners;

use App\Events\DemandeResidenceAcceptee;
use App\Services\Residents\SubServices\AbonnementCreationService;
use App\Services\Residents\SubServices\ResidentActivationService;
use Illuminate\Support\Facades\Log;

/**
 * Écouteur pour activer le statut résident et créer l'abonnement initial de 12 mois.
 */
class ActiverResidentEtAbonnement
{
    /**
     * Créer une nouvelle instance de l'écouteur.
     */
    public function __construct(
        protected ResidentActivationService $activationService,
        protected AbonnementCreationService $abonnementService
    ) {}

    /**
     * Traiter l'événement.
     */
    public function handle(DemandeResidenceAcceptee $event): void
    {
        $demande = $event->demande;
        $user = $demande->user;

        if ($user) {
            // Activer le statut résident. L'abonnement n'est plus créé
            // automatiquement : le résident le souscrit et le paie séparément
            // (POST /abonnements/souscrire).
            $this->activationService->activate($user);

            Log::info("ActiverResidentEtAbonnement : statut résident activé pour l'utilisateur ID {$user->id} (Demande ID: {$demande->id}). Abonnement à souscrire.");
        }
    }
}
