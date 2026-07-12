<?php

namespace App\Repositories\Eloquent;

use App\Models\Billet;
use App\Repositories\Contracts\BilletRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Implémentation Eloquent du dépôt pour la gestion des billets.
 */
class BilletRepository implements BilletRepositoryInterface
{
    /**
     * Trouver un billet par son identifiant.
     */
    public function find(string $id): ?Billet
    {
        return Billet::find($id);
    }

    /**
     * Créer un nouveau billet.
     */
    public function create(array $data): Billet
    {
        return Billet::create($data);
    }

    /**
     * Mettre à jour un billet existant.
     */
    public function update(string $id, array $data): Billet
    {
        $billet = Billet::findOrFail($id);
        $billet->update($data);
        return $billet;
    }

    /**
     * Supprimer un billet.
     */
    public function delete(string $id): bool
    {
        $billet = Billet::find($id);
        if ($billet) {
            return $billet->delete();
        }
        return false;
    }

    /**
     * Paginer la liste des billets.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Billet::paginate($perPage);
    }
}
