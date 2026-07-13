<?php

namespace App\Listeners;

use App\Enums\CanalEnum;
use App\Enums\NotificationEnum;
use App\Events\DemandeResidenceSoumise;
use App\Mail\NouvelleDemandeResidenceMail;
use App\Models\User;
use App\Services\Notifications\NotificationDispatchService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Écouteur pour notifier les agents/administrateurs lors de la soumission d'une nouvelle demande de résidence.
 */
class NotifierAgentNouvelleDemande
{
    /**
     * Créer une nouvelle instance de l'écouteur.
     */
    public function __construct(protected NotificationDispatchService $notifier) {}

    /**
     * Traiter l'événement.
     */
    public function handle(DemandeResidenceSoumise $event): void
    {
        $demande = $event->demande;
        $demandeur = $demande->user;

        if (! $demandeur) {
            return;
        }

        // Récupérer tous les administrateurs
        $admins = User::whereHas('role', function ($query) {
            $query->where('nom', 'Admin');
        })->get();

        $message = "Nouvelle demande de résidence soumise par {$demandeur->prenom} {$demandeur->nom} (ID: {$demande->id}).";

        foreach ($admins as $admin) {
            // Notification in-app + temps réel (Reverb).
            $this->notifier->dispatch(
                $admin,
                NotificationEnum::ALERTE,
                CanalEnum::IN_APP,
                $message
            );

            // Email détaillé.
            Mail::to($admin->email)->queue(new NouvelleDemandeResidenceMail($demande));
        }

        Log::info('NotifierAgentNouvelleDemande : Notification (in-app + mail) envoyée à '.$admins->count()." administrateurs pour la demande ID {$demande->id}");
    }
}
