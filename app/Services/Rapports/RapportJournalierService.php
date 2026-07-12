<?php

namespace App\Services\Rapports;

use App\Services\Rapports\SubServices\RapportVentesService;
use App\Services\Rapports\SubServices\RapportGainsService;
use App\Services\Rapports\SubServices\RapportFraudeService;
use Carbon\Carbon;

/**
 * Service pour la génération et la consolidation des rapports financiers, des ventes, et de fraude journaliers.
 */
class RapportJournalierService
{
    public function __construct(
        protected RapportVentesService $ventesService,
        protected RapportGainsService $gainsService,
        protected RapportFraudeService $fraudeService
    ) {
    }

    /**
     * Générer les métriques du rapport journalier.
     */
    public function generer(?\DateTimeInterface $date = null): array
    {
        $targetDate = $date ? Carbon::instance($date) : Carbon::today();

        $ventes = $this->ventesService->calculer($targetDate);
        $gains = $this->gainsService->calculer($targetDate);
        $fraude = $this->fraudeService->calculer($targetDate);

        return array_merge(
            ['date' => $targetDate->toDateString()],
            $ventes,
            $gains,
            $fraude
        );
    }
}
