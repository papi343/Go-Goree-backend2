<?php

namespace App\Console\Commands;

use App\Enums\CanalEnum;
use App\Enums\NotificationEnum;
use App\Events\NotificationCreee;
use App\Models\Notification;
use App\Models\Voyage;
use Illuminate\Console\Command;

class SendEmbarquementReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'goree:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Envoie des notifications de rappel en temps réel (15 min avant l'embarquement) aux passagers.";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Recherche des voyages débutant dans 15 minutes...");

        // On cible une plage de 14 à 16 minutes dans le futur pour éviter de rater un créneau
        $targetTimeStart = now()->addMinutes(14)->format('H:i:00');
        $targetTimeEnd = now()->addMinutes(16)->format('H:i:59');

        $voyages = Voyage::whereDate('date_voyage', now()->toDateString())
            ->whereHas('trajet', function ($q) use ($targetTimeStart, $targetTimeEnd) {
                $q->whereBetween('heure_depart', [$targetTimeStart, $targetTimeEnd]);
            })
            ->with(['trajet', 'billets.user'])
            ->get();

        $count = 0;
        foreach ($voyages as $voyage) {
            foreach ($voyage->billets as $billet) {
                if ($billet->statut === 'PAYE' && $billet->user) {
                    $user = $billet->user;

                    // Créer la notification in-app
                    $notification = Notification::create([
                        'type' => NotificationEnum::ALERTE,
                        'canal' => CanalEnum::IN_APP,
                        'lu_a' => null,
                        'user_id' => $user->id,
                    ]);

                    $message = "Rappel : Votre voyage à bord de la chaloupe '{$voyage->chaloupe->nom}' part à {$voyage->trajet->heure_depart}. Il vous reste environ 15 minutes avant l'embarquement.";

                    // Diffuser en temps réel avec Laravel Reverb
                    event(new NotificationCreee($notification, $message));

                    // Logger la simulation
                    \Illuminate\Support\Facades\Log::info("Rappel embarquement envoyé à l'utilisateur {$user->id} pour le voyage {$voyage->id}");
                    $count++;
                }
            }
        }

        $this->info("{$count} rappels d'embarquement envoyés avec succès.");
    }
}
