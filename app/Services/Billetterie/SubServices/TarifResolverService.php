<?php

namespace App\Services\Billetterie\SubServices;

use App\Models\Tarif;
use App\Models\User;
use App\Enums\CategorieEnum;

/**
 * Service pour déterminer et attribuer le tarif approprié à appliquer à un utilisateur lors de l'achat d'un billet.
 */
class TarifResolverService
{
    public function __construct(protected ResidentAbonnementCheckerService $abonnementChecker)
    {
    }

    /**
     * Résoudre le tarif approprié pour un utilisateur.
     */
    public function resolve(User $user, ?CategorieEnum $requestedCategory = null): Tarif
    {
        if ($this->abonnementChecker->check($user)) {
            return Tarif::where('categorie', CategorieEnum::RESIDENT)->firstOrFail();
        }

        $category = $requestedCategory ?: ($user->est_resident ? CategorieEnum::ADULTE : CategorieEnum::ETRANGER);

        if ($category === CategorieEnum::RESIDENT) {
            $category = CategorieEnum::ADULTE;
        }

        return Tarif::where('categorie', $category)->firstOrFail();
    }
}
