<?php

namespace App\Services\Rapports\SubServices;

use App\Models\Payement;
use App\Enums\StatutPayementEnum;
use Carbon\Carbon;

/**
 * Service pour le calcul et le cumul des gains financiers réalisés.
 */
class RapportGainsService
{
    /**
     * Calculer les métriques des gains financiers.
     */
    public function calculer(Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $totalGains = Payement::where('statut', StatutPayementEnum::ACCEPTE)
            ->whereBetween('created_at', [$start, $end])
            ->sum('montant');

        return [
            'total_gains' => (float) $totalGains,
        ];
    }
}
