<?php

namespace App\Services\Residents\SubServices;

use App\Models\Resident;
use App\Models\Abonnement;
use App\Repositories\Contracts\AbonnementRepositoryInterface;

/**
 * Service pour la création et la configuration initiale des abonnements annuels pour les résidents.
 */
class AbonnementCreationService
{
    public function __construct(protected AbonnementRepositoryInterface $abonnementRepository)
    {
    }

    /**
     * Créer un abonnement pour un résident.
     */
    public function create(Resident $resident, float $montant = 5000.0, int $durationMonths = 12): Abonnement
    {
        return $this->abonnementRepository->create([
            'resident_id' => $resident->id,
            'date_debut' => now(),
            'date_fin' => now()->addMonths($durationMonths),
            'montant' => $montant,
        ]);
    }
}
