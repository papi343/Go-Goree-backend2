<?php

namespace App\Repositories\Contracts;

use App\Models\Payement;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface pour la gestion des paiements.
 */
interface PayementRepositoryInterface
{
    /**
     * Trouver un paiement par son identifiant.
     */
    public function find(string $id): ?Payement;

    /**
     * Trouver un paiement par sa référence unique.
     */
    public function findByReference(string $reference): ?Payement;

    /**
     * Trouver un paiement par son jeton (token) de transaction.
     */
    public function findByToken(string $token): ?Payement;

    /**
     * Créer un nouveau paiement.
     */
    public function create(array $data): Payement;

    /**
     * Mettre à jour un paiement existant.
     */
    public function update(string $id, array $data): Payement;

    /**
     * Supprimer un paiement.
     */
    public function delete(string $id): bool;

    /**
     * Paginer la liste des paiements.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Obtenir les rapports de ventes sur une période donnée.
     */
    public function getSalesReports(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array;
}
