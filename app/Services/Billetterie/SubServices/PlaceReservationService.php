<?php

namespace App\Services\Billetterie\SubServices;

use App\Repositories\Contracts\VoyageRepositoryInterface;

/**
 * Service pour la réservation sécurisée et la décrémentation des places sur les chaloupes/voyages.
 */
class PlaceReservationService
{
    public function __construct(protected VoyageRepositoryInterface $voyageRepository)
    {
    }

    /**
     * Réserver des places pour un voyage.
     */
    public function reserve(string $voyageId, int $count = 1): bool
    {
        return $this->voyageRepository->decrementPlacesRestantes($voyageId, $count);
    }
}
