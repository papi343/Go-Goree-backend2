<?php

namespace App\Repositories\Eloquent;

use App\Models\Portefeuille;
use App\Models\MouvementPortefeuille;
use App\Enums\MouvementPortefeuilleEnum;
use App\Enums\StatutMouvementEnum;
use App\Repositories\Contracts\PortefeuilleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Implémentation Eloquent du dépôt pour la gestion des portefeuilles numériques.
 */
class PortefeuilleRepository implements PortefeuilleRepositoryInterface
{
    /**
     * Trouver un portefeuille par son identifiant.
     */
    public function find(string $id): ?Portefeuille
    {
        return Portefeuille::find($id);
    }

    /**
     * Trouver le portefeuille d'un utilisateur par son identifiant unique.
     */
    public function findByUserId(string $userId): ?Portefeuille
    {
        return Portefeuille::where('user_id', $userId)->first();
    }

    /**
     * Créer un nouveau portefeuille.
     */
    public function create(array $data): Portefeuille
    {
        return Portefeuille::create($data);
    }

    /**
     * Mettre à jour un portefeuille existant.
     */
    public function update(string $id, array $data): Portefeuille
    {
        $portefeuille = Portefeuille::findOrFail($id);
        $portefeuille->update($data);
        return $portefeuille;
    }

    /**
     * Supprimer un portefeuille.
     */
    public function delete(string $id): bool
    {
        $portefeuille = Portefeuille::find($id);
        if ($portefeuille) {
            return $portefeuille->delete();
        }
        return false;
    }

    /**
     * Paginer la liste des portefeuilles.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Portefeuille::paginate($perPage);
    }

    /**
     * Verrouiller le portefeuille d'un utilisateur, le créditer du montant indiqué et enregistrer le mouvement (recharge).
     * Crée automatiquement le portefeuille si celui-ci n'existe pas encore.
     */
    public function lockForUpdateAndCredit(string $userId, float $amount, ?string $payementId = null): Portefeuille
    {
        return DB::transaction(function () use ($userId, $amount, $payementId) {
            $portefeuille = Portefeuille::where('user_id', $userId)->lockForUpdate()->first();
            if (!$portefeuille) {
                $portefeuille = Portefeuille::create([
                    'user_id' => $userId,
                    'solde' => 0,
                ]);
            }

            $portefeuille->solde += $amount;
            $portefeuille->save();

            MouvementPortefeuille::create([
                'portefeuille_id' => $portefeuille->id,
                'montant' => $amount,
                'type' => MouvementPortefeuilleEnum::RECHARGE,
                'statut' => StatutMouvementEnum::VALIDE,
                'payement_id' => $payementId,
            ]);

            return $portefeuille;
        });
    }

    /**
     * Verrouiller le portefeuille d'un utilisateur, le débiter du montant indiqué et enregistrer le mouvement (débit).
     * Lève une exception si le portefeuille n'existe pas ou si son solde est insuffisant.
     */
    public function lockForUpdateAndDebit(string $userId, float $amount, ?string $payementId = null): Portefeuille
    {
        return DB::transaction(function () use ($userId, $amount, $payementId) {
            $portefeuille = Portefeuille::where('user_id', $userId)->lockForUpdate()->first();
            if (!$portefeuille) {
                throw new \Exception("Le portefeuille n'existe pas.");
            }

            if ($portefeuille->solde < $amount) {
                throw new \Exception("Solde insuffisant.");
            }

            $portefeuille->solde -= $amount;
            $portefeuille->save();

            MouvementPortefeuille::create([
                'portefeuille_id' => $portefeuille->id,
                'montant' => $amount,
                'type' => MouvementPortefeuilleEnum::DEBIT,
                'statut' => StatutMouvementEnum::VALIDE,
                'payement_id' => $payementId,
            ]);

            return $portefeuille;
        });
    }
}
