<?php

namespace App\Jobs;

use App\Models\Billet;
use App\Models\Voyage;
use App\Enums\StatutBilletEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job pour expirer automatiquement les billets 2 heures après l'heure de départ du voyage.
 */
class ExpireTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Exécuter la tâche.
     */
    public function handle(): void
    {
        // Calcul de l'heure pivot (il y a 2 heures)
        $twoHoursAgo = now()->subHours(2);

        // Récupérer les identifiants des voyages dont le départ a eu lieu il y a plus de 2 heures
        $expiredVoyageIds = Voyage::join('trajets', 'voyages.trajet_id', '=', 'trajets.id')
            ->where(function ($query) use ($twoHoursAgo) {
                // Option A : Le voyage a eu lieu un jour précédent
                $query->where('voyages.date_voyage', '<', $twoHoursAgo->toDateString())
                      // Option B : Le voyage a lieu aujourd'hui mais l'heure de départ est passée de plus de 2h
                      ->orWhere(function ($q) use ($twoHoursAgo) {
                          $q->where('voyages.date_voyage', '=', $twoHoursAgo->toDateString())
                            ->where('trajets.heure_depart', '<=', $twoHoursAgo->toTimeString());
                      });
            })
            ->pluck('voyages.id');

        // Si des voyages expirés sont trouvés, mettre à jour le statut des billets associés
        if ($expiredVoyageIds->isNotEmpty()) {
            Billet::whereIn('voyage_id', $expiredVoyageIds)
                // Cible uniquement les billets payés (non scannés) ou utilisés (scannés)
                ->whereIn('statut', [
                    StatutBilletEnum::PAYE,
                    StatutBilletEnum::UTILISE
                ])
                ->update(['statut' => StatutBilletEnum::EXPIRE]);
        }
    }
}
