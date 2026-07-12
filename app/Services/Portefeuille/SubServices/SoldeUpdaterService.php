<?php

namespace App\Services\Portefeuille\SubServices;

use App\Repositories\Contracts\PortefeuilleRepositoryInterface;
use App\Models\Portefeuille;

/**
 * Service interne pour mettre à jour le solde d'un portefeuille numérique (crédit/débit).
 */
class SoldeUpdaterService
{
    public function __construct(protected PortefeuilleRepositoryInterface $portefeuilleRepository)
    {
    }

    /**
     * Créditer le portefeuille d'un utilisateur du montant indiqué.
     */
    public function credit(string $userId, float $amount, ?string $payementId = null): Portefeuille
    {
        return $this->portefeuilleRepository->lockForUpdateAndCredit($userId, $amount, $payementId);
    }

    /**
     * Débiter le portefeuille d'un utilisateur du montant indiqué.
     */
    public function debit(string $userId, float $amount, ?string $payementId = null): Portefeuille
    {
        return $this->portefeuilleRepository->lockForUpdateAndDebit($userId, $amount, $payementId);
    }
}
