<?php

namespace App\Services\Portefeuille\SubServices;

use App\Models\MouvementPortefeuille;
use App\Enums\MouvementPortefeuilleEnum;
use App\Enums\StatutMouvementEnum;

/**
 * Fabrique pour la création simplifiée d'enregistrements de mouvements de portefeuille (historique).
 */
class MouvementPortefeuilleFactoryService
{
    /**
     * Générer et enregistrer un nouveau mouvement de portefeuille.
     */
    public function make(string $portefeuilleId, float $amount, MouvementPortefeuilleEnum $type, StatutMouvementEnum $statut, ?string $payementId = null): MouvementPortefeuille
    {
        return MouvementPortefeuille::create([
            'portefeuille_id' => $portefeuilleId,
            'montant' => $amount,
            'type' => $type,
            'statut' => $statut,
            'payement_id' => $payementId,
        ]);
    }
}
