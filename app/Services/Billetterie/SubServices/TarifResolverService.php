<?php

namespace App\Services\Billetterie\SubServices;

use App\Enums\CategorieEnum;
use App\Models\Tarif;
use App\Models\User;

/**
 * Détermine le tarif applicable à un utilisateur pour un billet.
 *
 * Un résident (statut de résidence validé) bénéficie du tarif réduit RESIDENT,
 * qu'il soit abonné ou non. La gratuité éventuelle liée à un abonnement actif
 * est gérée en amont par BilletPurchaseService (montant forcé à 0).
 */
class TarifResolverService
{
    public function resolve(User $user, ?CategorieEnum $requestedCategory = null): Tarif
    {
        // Résident : tarif réduit dédié.
        if ($user->est_resident && $user->resident) {
            return Tarif::where('categorie', CategorieEnum::RESIDENT)->firstOrFail();
        }

        // Non-résident : catégorie demandée (enfant/adulte/étranger), défaut étranger.
        $category = $requestedCategory ?: CategorieEnum::ETRANGER;

        // Un non-résident ne peut pas réclamer le tarif RESIDENT.
        if ($category === CategorieEnum::RESIDENT) {
            $category = CategorieEnum::ADULTE;
        }

        return Tarif::where('categorie', $category)->firstOrFail();
    }
}
