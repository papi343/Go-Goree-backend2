<?php

namespace App\Services\Rapports\SubServices;

use App\Models\AlerteFraude;
use App\Enums\StatutAlerteFraudeEnum;
use Carbon\Carbon;

/**
 * Service pour le calcul et l'extraction des indicateurs liés aux alertes de fraude.
 */
class RapportFraudeService
{
    /**
     * Calculer les métriques liées aux alertes de fraude.
     */
    public function calculer(Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $totalAlertes = AlerteFraude::whereBetween('created_at', [$start, $end])->count();
        
        $enAttente = AlerteFraude::where('statut', StatutAlerteFraudeEnum::EN_ATTENTE)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $confirmees = AlerteFraude::where('statut', StatutAlerteFraudeEnum::CONFIRMEE)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return [
            'total_alertes_fraude' => $totalAlertes,
            'alertes_en_attente' => $enAttente,
            'alertes_confirmees' => $confirmees,
        ];
    }
}
