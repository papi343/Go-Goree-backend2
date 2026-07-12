<?php

namespace App\Services\Residents;

use App\Models\DemandeResidence;
use App\Models\User;
use App\Enums\DemandeResidenceEnum;
use App\Services\Residents\SubServices\ResidentActivationService;
use App\Services\Residents\SubServices\AbonnementCreationService;
use App\Events\DemandeResidenceAcceptee;
use App\Events\DemandeResidenceRefusee;
use Illuminate\Support\Facades\DB;

/**
 * Service pour la validation administrative des demandes de résidence.
 * Gère l'acceptation (activation du résident et création de l'abonnement initial) ou le refus des demandes.
 */
class DemandeResidenceValidationService
{
    public function __construct(
        protected ResidentActivationService $activationService,
        protected AbonnementCreationService $abonnementService
    ) {
    }

    /**
     * Valider une demande de résidence.
     */
    public function valider(DemandeResidence $demande, User $admin): void
    {
        DB::transaction(function () use ($demande, $admin) {
            $demande->update([
                'statut' => DemandeResidenceEnum::ACCEPTEE,
                'valide_par' => $admin->id,
                'date_validation' => now(),
            ]);

            $resident = $this->activationService->activate($demande->user);

            $this->abonnementService->create($resident, 5000.0, 12);

            event(new DemandeResidenceAcceptee($demande));
        });
    }

    /**
     * Refuser une demande de résidence.
     */
    public function refuser(DemandeResidence $demande, string $motif, User $admin): void
    {
        $demande->update([
            'statut' => DemandeResidenceEnum::REFUSEE,
            'motif_refus' => $motif,
            'valide_par' => $admin->id,
            'date_validation' => now(),
        ]);

        event(new DemandeResidenceRefusee($demande));
    }
}
