<?php

namespace App\Repositories\Contracts;

use App\Models\Abonnement;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface pour la gestion des abonnements.
 */
interface AbonnementRepositoryInterface
{
    /**
     * Trouver un abonnement par son identifiant.
     */
    public function find(string $id): ?Abonnement;

    /**
     * Créer un nouvel abonnement.
     */
    public function create(array $data): Abonnement;

    /**
     * Mettre à jour un abonnement existant.
     */
    public function update(string $id, array $data): Abonnement;

    /**
     * Supprimer un abonnement.
     */
    public function delete(string $id): bool;

    /**
     * Paginer la liste des abonnements.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Obtenir l'abonnement actif d'un résident donné.
     */
    public function activeForResident(string $residentId): ?Abonnement;
}
