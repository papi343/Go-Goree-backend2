<?php

namespace App\Repositories\Contracts;

use App\Models\Portefeuille;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface pour la gestion des portefeuilles numériques (wallets).
 */
interface PortefeuilleRepositoryInterface
{
    /**
     * Trouver un portefeuille par son identifiant.
     */
    public function find(string $id): ?Portefeuille;

    /**
     * Trouver le portefeuille associé à un utilisateur.
     */
    public function findByUserId(string $userId): ?Portefeuille;

    /**
     * Créer un nouveau portefeuille.
     */
    public function create(array $data): Portefeuille;

    /**
     * Mettre à jour un portefeuille existant.
     */
    public function update(string $id, array $data): Portefeuille;

    /**
     * Supprimer un portefeuille.
     */
    public function delete(string $id): bool;

    /**
     * Paginer la liste des portefeuilles.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Verrouiller le portefeuille pour mise à jour (Pessimistic Locking) et le créditer d'un montant.
     */
    public function lockForUpdateAndCredit(string $userId, float $amount, ?string $payementId = null): Portefeuille;

    /**
     * Verrouiller le portefeuille pour mise à jour (Pessimistic Locking) et le débiter d'un montant.
     */
    public function lockForUpdateAndDebit(string $userId, float $amount, ?string $payementId = null): Portefeuille;
}
