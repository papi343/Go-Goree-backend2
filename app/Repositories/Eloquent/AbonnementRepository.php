<?php

namespace App\Repositories\Eloquent;

use App\Models\Abonnement;
use App\Repositories\Contracts\AbonnementRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Implémentation Eloquent du dépôt pour la gestion des abonnements.
 */
class AbonnementRepository implements AbonnementRepositoryInterface
{
    /**
     * Trouver un abonnement par son identifiant.
     */
    public function find(string $id): ?Abonnement
    {
        return Abonnement::find($id);
    }

    /**
     * Créer un nouvel abonnement.
     */
    public function create(array $data): Abonnement
    {
        return Abonnement::create($data);
    }

    /**
     * Mettre à jour un abonnement existant.
     */
    public function update(string $id, array $data): Abonnement
    {
        $abonnement = Abonnement::findOrFail($id);
        $abonnement->update($data);
        return $abonnement;
    }

    /**
     * Supprimer un abonnement par son identifiant.
     */
    public function delete(string $id): bool
    {
        $abonnement = Abonnement::find($id);
        if ($abonnement) {
            return $abonnement->delete();
        }
        return false;
    }

    /**
     * Paginer la liste des abonnements.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Abonnement::paginate($perPage);
    }

    /**
     * Obtenir l'abonnement actif d'un résident donné.
     * (Vérifie que la date de fin est supérieure à la date actuelle)
     */
    public function activeForResident(string $residentId): ?Abonnement
    {
        return Abonnement::where('resident_id', $residentId)
            ->where('date_fin', '>', now())
            ->orderBy('date_fin', 'desc')
            ->first();
    }
}
