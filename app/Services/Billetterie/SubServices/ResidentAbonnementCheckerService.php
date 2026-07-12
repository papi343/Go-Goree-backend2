<?php

namespace App\Services\Billetterie\SubServices;

use App\Repositories\Contracts\AbonnementRepositoryInterface;
use App\Models\User;

/**
 * Service pour vérifier si un utilisateur possède le statut de résident avec un abonnement actif en cours de validité.
 */
class ResidentAbonnementCheckerService
{
    public function __construct(protected AbonnementRepositoryInterface $abonnementRepository)
    {
    }

    /**
     * Vérifier si l'utilisateur est un résident avec un abonnement actif.
     */
    public function check(User $user): bool
    {
        if (!$user->est_resident || !$user->resident) {
            return false;
        }

        $activeAbonnement = $this->abonnementRepository->activeForResident($user->resident->id);

        return $activeAbonnement !== null && $activeAbonnement->estActif();
    }
}
