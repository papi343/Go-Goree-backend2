<?php

namespace App\Repositories\Eloquent;

use App\Models\Voyage;
use App\Repositories\Contracts\VoyageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Implémentation Eloquent du dépôt pour la gestion des voyages.
 */
class VoyageRepository implements VoyageRepositoryInterface
{
    /**
     * Trouver un voyage par son identifiant.
     */
    public function find(string $id): ?Voyage
    {
        return Voyage::find($id);
    }

    /**
     * Créer un nouveau voyage.
     */
    public function create(array $data): Voyage
    {
        return Voyage::create($data);
    }

    /**
     * Mettre à jour un voyage existant.
     */
    public function update(string $id, array $data): Voyage
    {
        $voyage = Voyage::findOrFail($id);
        $voyage->update($data);
        return $voyage;
    }

    /**
     * Supprimer un voyage.
     */
    public function delete(string $id): bool
    {
        $voyage = Voyage::find($id);
        if ($voyage) {
            return $voyage->delete();
        }
        return false;
    }

    /**
     * Paginer la liste des voyages.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Voyage::paginate($perPage);
    }

    /**
     * Décrémenter de manière sécurisée (avec lock de mise à jour) le nombre de places restantes pour un voyage.
     * Renvoie true si l'opération a réussi, false si le voyage n'existe pas ou s'il n'y a plus assez de places.
     */
    public function decrementPlacesRestantes(string $id, int $count = 1): bool
    {
        return DB::transaction(function () use ($id, $count) {
            $voyage = Voyage::where('id', $id)->lockForUpdate()->first();
            
            if ($voyage && $voyage->places_restantes >= $count) {
                $voyage->places_restantes -= $count;
                $voyage->save();
                return true;
            }
            
            return false;
        });
    }
}
