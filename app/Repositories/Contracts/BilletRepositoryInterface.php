<?php

namespace App\Repositories\Contracts;

use App\Models\Billet;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface pour la gestion des billets de transport.
 */
interface BilletRepositoryInterface
{
    /**
     * Trouver un billet par son identifiant.
     */
    public function find(string $id): ?Billet;

    /**
     * Créer un nouveau billet.
     */
    public function create(array $data): Billet;

    /**
     * Mettre à jour un billet existant.
     */
    public function update(string $id, array $data): Billet;

    /**
     * Supprimer un billet.
     */
    public function delete(string $id): bool;

    /**
     * Paginer la liste des billets.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
