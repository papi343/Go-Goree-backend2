<?php

namespace App\Jobs;

use App\Enums\JourEnum;
use App\Enums\StatutChaloupeEnum;
use App\Models\Chaloupe;
use App\Models\Trajet;
use App\Models\Voyage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Génère (de façon idempotente) les voyages des 7 prochains jours à partir des
 * trajets récurrents (chaque Trajet définit un jour de la semaine + une heure).
 *
 * Exécuté chaque soir : on dispose toujours de la liste sur 7 jours glissants.
 */
class GenererVoyagesSemaineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  int  $jours  Nombre de jours à couvrir à partir d'aujourd'hui.
     */
    public function __construct(public int $jours = 7) {}

    public function handle(): void
    {
        $chaloupes = Chaloupe::where('statut', StatutChaloupeEnum::ACTIVE->value)->get();

        if ($chaloupes->isEmpty()) {
            Log::warning('GenererVoyagesSemaineJob : aucune chaloupe active, génération ignorée.');

            return;
        }

        $trajets = Trajet::all();
        $index = 0;
        $crees = 0;

        for ($i = 0; $i < $this->jours; $i++) {
            $date = Carbon::today()->addDays($i);
            $jour = $this->jourEnum($date);

            foreach ($trajets as $trajet) {
                if ($trajet->jour !== $jour) {
                    continue;
                }

                $existe = Voyage::where('trajet_id', $trajet->id)
                    ->whereDate('date_voyage', $date->toDateString())
                    ->exists();

                if ($existe) {
                    continue;
                }

                // Répartition à tour de rôle (round-robin) des chaloupes actives.
                $chaloupe = $chaloupes[$index % $chaloupes->count()];
                $index++;

                try {
                    Voyage::create([
                        'date_voyage' => $date->toDateString(),
                        'places' => $chaloupe->capacite,
                        'places_restantes' => $chaloupe->capacite,
                        'trajet_id' => $trajet->id,
                        'chaloupe_id' => $chaloupe->id,
                    ]);
                    $crees++;
                } catch (UniqueConstraintViolationException $e) {
                    // Voyage déjà généré (exécution concurrente) : on ignore.
                }
            }
        }

        Log::info("GenererVoyagesSemaineJob : {$crees} voyage(s) généré(s) sur {$this->jours} jours.");
    }

    /**
     * Convertit une date en JourEnum (LUNDI…DIMANCHE) selon son jour de semaine.
     */
    private function jourEnum(Carbon $date): JourEnum
    {
        return match ($date->dayOfWeekIso) {
            1 => JourEnum::LUNDI,
            2 => JourEnum::MARDI,
            3 => JourEnum::MERCREDI,
            4 => JourEnum::JEUDI,
            5 => JourEnum::VENDREDI,
            6 => JourEnum::SAMEDI,
            default => JourEnum::DIMANCHE,
        };
    }
}
