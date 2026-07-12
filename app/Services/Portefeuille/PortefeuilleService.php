<?php

namespace App\Services\Portefeuille;

use App\Services\Portefeuille\SubServices\SoldeUpdaterService;
use App\Models\Portefeuille;
use App\Events\PortefeuilleRecharge;
use App\Events\PortefeuilleDebite;

/**
 * Service pour la gestion globale des actions sur les portefeuilles numériques (crédit, débit).
 */
class PortefeuilleService
{
    public function __construct(protected SoldeUpdaterService $soldeUpdaterService)
    {
    }

    /**
     * Débiter un montant du portefeuille d'un utilisateur et déclencher l'événement associé.
     */
    public function debiter(string $userId, float $amount, ?string $payementId = null): Portefeuille
    {
        $portefeuille = $this->soldeUpdaterService->debit($userId, $amount, $payementId);
        
        event(new PortefeuilleDebite($portefeuille, $amount));

        return $portefeuille;
    }

    /**
     * Recharger (créditer) le portefeuille d'un utilisateur et déclencher l'événement associé.
     */
    public function recharger(string $userId, float $amount, ?string $payementId = null): Portefeuille
    {
        $portefeuille = $this->soldeUpdaterService->credit($userId, $amount, $payementId);

        event(new PortefeuilleRecharge($portefeuille, $amount));

        return $portefeuille;
    }
}
