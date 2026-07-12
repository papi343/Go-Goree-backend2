<?php

namespace App\Repositories\Contracts;

use App\Models\Voyage;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface pour la gestion des voyages.
 */
interface VoyageRepositoryInterface
{
    /**
     * Trouver un voyage par son identifiant.
     */
    public function find(string $id): ?Voyage;

    /**
     * Créer un nouveau voyage.
     */
    public function create(array $data): Voyage;

    /**
     * Mettre à jour un voyage existant.
     */
    public function update(string $id, array $data): Voyage;

    /**
     * Supprimer un voyage.
     */
    public function delete(string $id): bool;

    /**
     * Paginer la liste des voyages.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Décrémenter le nombre de places restantes pour un voyage donné.
     */
    public function decrementPlacesRestantes(string $id, int $count = 1): bool;
}
