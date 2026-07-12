<?php

namespace App\Services\Rapports\SubServices;

use App\Models\Billet;
use App\Enums\StatutBilletEnum;
use Carbon\Carbon;

/**
 * Service pour le calcul et le suivi statistique des ventes et de l'utilisation des billets.
 */
class RapportVentesService
{
    /**
     * Calculer les métriques des ventes de billets.
     */
    public function calculer(Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $totalBillets = Billet::whereBetween('created_at', [$start, $end])->count();
        
        $payes = Billet::where('statut', StatutBilletEnum::PAYE)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $utilises = Billet::where('statut', StatutBilletEnum::UTILISE)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return [
            'total_billets' => $totalBillets,
            'billets_payes' => $payes,
            'billets_utilises' => $utilises,
        ];
    }
}
